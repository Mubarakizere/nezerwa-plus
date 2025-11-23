<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Expense extends Model
{
    public const METHODS = ['cash','bank','momo','mobile_money'];

    protected $fillable = [
        'date','amount','category_id','supplier_id',
        'method','reference','note','created_by',
    ];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];

    // --- Relationships ---
    public function category(): BelongsTo
    {
        // Keep withTrashed only if Category uses SoftDeletes
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        // Remove withTrashed unless Supplier actually uses SoftDeletes
        return $this->belongsTo(Supplier::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // --- Scopes ---
    public function scopeBetween(Builder $q, $from = null, $to = null): Builder
    {
        if ($from && $to && $from > $to) [$from, $to] = [$to, $from];
        if ($from) $q->whereDate('date', '>=', $from);
        if ($to)   $q->whereDate('date', '<=', $to);
        return $q;
    }

    public function scopeMethod(Builder $q, ?string $method): Builder
    {
        if ($method) $q->where('method', strtolower($method));
        return $q;
    }

    public function scopeCategory(Builder $q, ?int $categoryId): Builder
    {
        if ($categoryId) $q->where('category_id', $categoryId);
        return $q;
    }

    // 🔧 renamed to avoid collision with the relationship:
    public function scopeForSupplier(Builder $q, ?int $supplierId): Builder
    {
        if ($supplierId) $q->where('supplier_id', $supplierId);
        return $q;
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) return $q;

        $op   = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $wild = "%{$term}%";

        return $q->where(function ($w) use ($op, $wild) {
            $w->where('reference', $op, $wild)
              ->orWhere('note', $op, $wild)
              ->orWhereHas('category', fn ($c) => $c->where('name', $op, $wild))
              ->orWhereHas('supplier', fn ($s) => $s->where('name', $op, $wild));
        });
    }
}
