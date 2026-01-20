<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductTemplateExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $categories;

    public function __construct($categories)
    {
        $this->categories = $categories;
    }

    /**
     * Return sample data
     */
    public function collection()
    {
        // Include example rows
        $categories = $this->categories;
        $firstCategory = $categories->first()->name ?? 'General';
        
        return collect([
            ['Example Product 1', $firstCategory, 1000, 800, 50],
            ['Example Product 2', $firstCategory, 2500, 2000, 100],
            ['', '', '', '', ''], // Empty row for user to fill
        ]);
    }

    /**
     * Column headers
     */
    public function headings(): array
    {
        return [
            'Product Name',
            'Category',
            'Selling Price',
            'Cost Price',
            'Stock Quantity',
        ];
    }

    /**
     * Style the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Style example rows (gray background)
        $sheet->getStyle('A2:E3')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6'],
            ],
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '6B7280'],
            ],
        ]);

        // Add notes/instructions
        $sheet->setCellValue('A5', 'INSTRUCTIONS:');
        $sheet->setCellValue('A6', '1. Delete the example rows (rows 2-3) before importing');
        $sheet->setCellValue('A7', '2. Fill in your product data starting from row 2');
        $sheet->setCellValue('A8', '3. Product Name is required and should be unique');
        $sheet->setCellValue('A9', '4. Category must match one of the categories below');
        $sheet->setCellValue('A10', '5. Prices and stock must be numbers (0 or greater)');
        
        // Bold instructions
        $sheet->getStyle('A5:A10')->getFont()->setBold(true);
        $sheet->getStyle('A5:A10')->getFont()->setSize(10);
        
    // Add available categories
        $sheet->setCellValue('A12', 'AVAILABLE CATEGORIES:');
        $sheet->getStyle('A12')->getFont()->setBold(true);
        $sheet->getStyle('A12')->getFont()->setSize(11);
        $sheet->getStyle('A12')->getFont()->getColor()->setRGB('059669');
        
        $row = 13;
        foreach ($this->categories as $category) {
            $sheet->setCellValue("A{$row}", $category->name);
            $row++;
        }

        return $sheet;
    }

    /**
     * Column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 20,
            'C' => 15,
            'D' => 15,
            'E' => 15,
        ];
    }
}
