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


    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }


    public function currentStock(): float
    {
        $in  = (float) $this->stockMovements()->where('type', 'in')->sum('quantity');
        $out = (float) $this->stockMovements()->where('type', 'out')->sum('quantity');
        return round($in - $out, 2);
    }

    public function weightedAverageCost(): float
    {
        $row = $this->stockMovements()
            ->where('type', 'in')
            ->selectRaw('COALESCE(SUM(quantity),0) as qty')
            ->selectRaw('COALESCE(SUM(quantity * unit_cost),0) as cost')
            ->first();

        $qty  = (float)($row->qty ?? 0);
        $cost = (float)($row->cost ?? 0);

        return $qty > 0
            ? round($cost / $qty, 2)
            : round((float)($this->cost_price ?? 0), 2);
    }


    public function stockValue(): float
    {
        return round($this->currentStock() * $this->weightedAverageCost(), 2);
    }


    public function getFormattedStockAttribute(): string
    {
        return number_format($this->currentStock(), 0);
    }

    public function isLowStock(): bool
    {
        return $this->currentStock() <= 5;
    }
}
