<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const METHODS = ['cash','bank','momo'];

    protected $fillable = [
        'date', 'amount', 'category_id', 'supplier_id',
        'method', 'reference', 'note', 'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes (for filters & reporting)
    public function scopeBetween(Builder $q, $from = null, $to = null): Builder
    {
        if ($from) $q->whereDate('date', '>=', $from);
        if ($to)   $q->whereDate('date', '<=', $to);
        return $q;
    }

    public function scopeMethod(Builder $q, ?string $method): Builder
    {
        if ($method) $q->where('method', $method);
        return $q;
    }

    public function scopeCategory(Builder $q, ?int $categoryId): Builder
    {
        if ($categoryId) $q->where('category_id', $categoryId);
        return $q;
    }

    public function scopeSupplier(Builder $q, ?int $supplierId): Builder
    {
        if ($supplierId) $q->where('supplier_id', $supplierId);
        return $q;
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) return $q;
        return $q->where(function ($w) use ($term) {
            $w->where('reference', 'like', "%{$term}%")
              ->orWhere('note', 'like', "%{$term}%")
              ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$term}%"))
              ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$term}%"));
        });
    }
}
