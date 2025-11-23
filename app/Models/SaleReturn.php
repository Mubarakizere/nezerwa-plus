<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturn extends Model
{
    public const METHODS = ['cash','bank','momo','mobile_money'];

    protected $fillable = [
        'sale_id', 'date', 'amount', 'method', 'reference', 'reason', 'created_by',
    ];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function items()
{
    return $this->hasMany(\App\Models\SaleReturnItem::class, 'sale_return_id');
}

    // scopes (handy for reports)
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

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) return $q;
        return $q->where(function ($w) use ($term) {
            $w->where('reference', 'like', "%{$term}%")
              ->orWhere('reason', 'like', "%{$term}%");
        });
    }
}
