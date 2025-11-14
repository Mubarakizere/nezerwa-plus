<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    /**
     * Mass-assignable fields.
     * (Include sku only if you have the column; harmless if unused.)
     */
    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'price',
        'cost_price',
        'stock',
    ];

    /** Cast numeric money fields for consistency */
    protected $casts = [
        'price'      => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock'      => 'decimal:2',
    ];

    /** Optionally expose a formatted stock string in JSON if needed */
    protected $appends = ['formatted_stock'];

    /* =========================================================================
     | Relationships
     |======================================================================== */

    public function category(): BelongsTo
    {
        // Category supports SoftDeletes; show even if soft-deleted
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /* =========================================================================
     | Query scopes (handy for controllers)
     |======================================================================== */

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!($term = trim((string) $term))) return $q;

        return $q->where(function ($w) use ($term) {
            $w->where('name', 'like', "%{$term}%")
              ->orWhere('sku',  'like', "%{$term}%");
        });
    }

    public function scopeCategoryId(Builder $q, $categoryId): Builder
    {
        return $categoryId ? $q->where('category_id', $categoryId) : $q;
    }

    /* =========================================================================
     | Computed / Helpers
     |======================================================================== */

    /** Current stock = IN - OUT (from movements) */
    public function currentStock(): float
    {
        $in  = (float) $this->stockMovements()->where('type', 'in')->sum('quantity');
        $out = (float) $this->stockMovements()->where('type', 'out')->sum('quantity');
        return round($in - $out, 2);
    }

    /** Weighted Average Cost computed from IN movements */
    public function weightedAverageCost(): float
    {
        $row = $this->stockMovements()
            ->where('type', 'in')
            ->selectRaw('COALESCE(SUM(quantity),0)       as qty')
            ->selectRaw('COALESCE(SUM(quantity*unit_cost),0) as cost')
            ->first();

        $qty  = (float)($row->qty  ?? 0);
        $cost = (float)($row->cost ?? 0);

        return $qty > 0
            ? round($cost / $qty, 2)
            : round((float)($this->cost_price ?? 0), 2);
    }

    /** Monetary value of current stock (units * WAC) */
    public function stockValue(): float
    {
        return round($this->currentStock() * $this->weightedAverageCost(), 2);
    }

    /** Human-friendly stock display */
    public function getFormattedStockAttribute(): string
    {
        return number_format($this->currentStock(), 0);
    }

    /** Low-stock flag, configurable via config('inventory.low_stock', 5) */
    public function isLowStock(?int $threshold = null): bool
    {
        $threshold = $threshold ?? (int) config('inventory.low_stock', 5);
        return $this->currentStock() <= $threshold;
    }
}
