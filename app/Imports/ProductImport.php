<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class ProductImport
{
    protected array $errors = [];
    protected array $parsed = [];
    protected array $stats = [
        'total' => 0,
        'new' => 0,
        'existing' => 0,
        'errors' => 0,
    ];

    /**
     * Parse Excel data and validate
     * Returns array with parsed data, errors, and stats
     */
    public function parse(Collection $rows): array
    {
        $this->errors = [];
        $this->parsed = [];
        $this->stats = ['total' => 0, 'new' => 0, 'existing' => 0, 'errors' => 0];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because: 0-indexed + header row
            
            // Skip empty rows
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $this->stats['total']++;

            // Parse and validate row
            $parsedRow = $this->parseRow($row, $rowNumber);
            
            if ($parsedRow['status'] === 'error') {
                $this->stats['errors']++;
                $this->errors[] = $parsedRow;
            } else {
                if ($parsedRow['status'] === 'new') {
                    $this->stats['new']++;
                } else {
                    $this->stats['existing']++;
                }
                $this->parsed[] = $parsedRow;
            }
        }

        return [
            'parsed' => $this->parsed,
            'errors' => $this->errors,
            'stats' => $this->stats,
        ];
    }

    /**
     * Parse a single row
     */
    protected function parseRow(array $row, int $rowNumber): array
    {
        // Extract data from row (assuming columns: Name, Category, Price, Cost Price, Stock)
        $data = [
            'name' => trim($row[0] ?? ''),
            'category' => trim($row[1] ?? ''),
            'price' => $row[2] ?? null,
            'cost_price' => $row[3] ?? null,
            'stock' => $row[4] ?? null,
        ];

        // Validate
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return [
                'row' => $rowNumber,
                'status' => 'error',
                'data' => $data,
                'errors' => $validator->errors()->all(),
            ];
        }

        // Check if product exists
        $existingProduct = Product::where('name', $data['name'])->first();
        
        // Find category
        $category = Category::where('name', $data['category'])
            ->where('is_active', true)
            ->whereIn('kind', ['product', 'both'])
            ->first();

        if (!$category) {
            return [
                'row' => $rowNumber,
                'status' => 'error',
                'data' => $data,
                'errors' => ["Category '{$data['category']}' not found or inactive"],
            ];
        }

        return [
            'row' => $rowNumber,
            'status' => $existingProduct ? 'existing' : 'new',
            'data' => $data,
            'category_id' => $category->id,
            'existing_product' => $existingProduct,
            'current_stock' => $existingProduct ? $existingProduct->currentStock() : 0,
        ];
    }

    /**
     * Execute import with selected mode
     */
    public function execute(array $parsedData, string $mode, int $userId): array
    {
        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach ($parsedData as $item) {
            try {
                if ($item['status'] === 'new') {
                    $this->createProduct($item, $userId);
                } else {
                    $this->updateProduct($item, $mode, $userId);
                }
                $imported++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'row' => $item['row'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Create new product
     */
    protected function createProduct(array $item, int $userId): void
    {
        $product = Product::create([
            'name' => $item['data']['name'],
            'category_id' => $item['category_id'],
            'price' => $item['data']['price'],
            'cost_price' => $item['data']['cost_price'] ?? $item['data']['price'],
            'stock' => $item['data']['stock'],
        ]);

        // Create initial stock movement
        if ($item['data']['stock'] > 0) {
            \App\Models\StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $item['data']['stock'],
                'unit_cost' => $product->cost_price,
                'total_cost' => $item['data']['stock'] * $product->cost_price,
                'source_type' => 'Excel Import',
                'source_id' => null,
                'user_id' => $userId,
                'notes' => 'Initial stock from Excel import',
            ]);
        }
    }

    /**
     * Update existing product
     */
    protected function updateProduct(array $item, string $mode, int $userId): void
    {
        $product = $item['existing_product'];
        
        // Update price and cost if changed
        $product->update([
            'price' => $item['data']['price'],
            'cost_price' => $item['data']['cost_price'] ?? $item['data']['price'],
        ]);

        $currentStock = $item['current_stock'];
        $excelStock = (float) $item['data']['stock'];

        if ($mode === 'replace') {
            // Replace mode: Calculate difference and adjust
            $difference = $excelStock - $currentStock;
            
            if ($difference != 0) {
                \App\Models\StockMovement::create([
                    'product_id' => $product->id,
                    'type' => $difference > 0 ? 'in' : 'out',
                    'quantity' => abs($difference),
                    'unit_cost' => $product->cost_price,
                    'total_cost' => abs($difference) * $product->cost_price,
                    'source_type' => 'Excel Import (Replace)',
                    'source_id' => null,
                    'user_id' => $userId,
                    'notes' => "Stock adjusted from {$currentStock} to {$excelStock}",
                ]);
            }
        } else {
            // Add mode: Add to existing stock
            if ($excelStock > 0) {
                \App\Models\StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $excelStock,
                    'unit_cost' => $product->cost_price,
                    'total_cost' => $excelStock * $product->cost_price,
                    'source_type' => 'Excel Import (Add)',
                    'source_id' => null,
                    'user_id' => $userId,
                    'notes' => "Added {$excelStock} units to existing stock of {$currentStock}",
                ]);
            }
        }
    }

    /**
     * Check if row is empty
     */
    protected function isEmptyRow(array $row): bool
    {
        return empty(array_filter($row, fn($cell) => !empty(trim($cell ?? ''))));
    }
}
