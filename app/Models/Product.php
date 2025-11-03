<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'price',
        'cost_price',
        'stock',
    ];

    /**
     * Relationships
     * ---------------------------------------------------------------------
     */

    // 🔹 Each product belongs to one category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // 🔹 Product has many stock movements (in/out records)
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    // 🔹 Product appears in many purchase items
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // 🔹 Product appears in many sale items
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Helpers
     * ---------------------------------------------------------------------
     */

    /**
     * Compute current stock dynamically from movements.
     * (Always accurate, even if manual edits are made.)
     */
    public function currentStock(): float
    {
        $in  = $this->stockMovements()->where('type', 'in')->sum('quantity');
        $out = $this->stockMovements()->where('type', 'out')->sum('quantity');
        return round($in - $out, 2);
    }

    /**
     * Compute total value of stock on hand (based on cost price).
     */
    public function stockValue(): float
    {
        return round($this->currentStock() * ($this->cost_price ?? 0), 2);
    }

    /**
     * Attribute accessor — returns formatted stock number.
     */
    public function getFormattedStockAttribute(): string
    {
        return number_format($this->currentStock(), 0);
    }

    /**
     * Quick helper: detect low stock
     */
    public function isLowStock(): bool
    {
        return $this->currentStock() <= 5; // adjustable threshold
    }
}
