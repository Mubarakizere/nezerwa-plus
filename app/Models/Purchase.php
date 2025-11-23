<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    /* ───────────── Constants ───────────── */
    public const STATUSES = ['pending','partial','completed','cancelled'];
    public const CHANNELS = ['cash','bank','momo','mobile_money'];

    /* ───────────── Fillable ───────────── */
    protected $fillable = [
        'supplier_id',
        'user_id',
        'invoice_number',
        'purchase_date',
        'status',
        'subtotal',
        'tax',
        'discount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'notes',
        'payment_channel',
        'method',
    ];

    /* ───────────── Casts ───────────── */
    protected $casts = [
        'purchase_date' => 'date',
        'subtotal'      => 'decimal:2',
        'tax'           => 'decimal:2',
        'discount'      => 'decimal:2',
        'total_amount'  => 'decimal:2',
        'amount_paid'   => 'decimal:2',
        'balance_due'   => 'decimal:2',
    ];

    /* ───────────── Relationships ───────────── */

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function loan()
    {
        return $this->hasOne(Loan::class);
    }

    /* ───────────── Scopes (optional helpers) ───────────── */

    public function scopeChannel($q, ?string $channel)
    {
        if (!$channel) return $q;
        return $q->where('payment_channel', strtolower(trim($channel)));
    }

    public function scopeStatus($q, ?string $status)
    {
        if (!$status) return $q;
        return $q->where('status', strtolower(trim($status)));
    }

    /* ───────────── Mutators / Accessors ───────────── */

    /** Normalize channel on assignment (prevents stray values and fixes legacy forms). */
    public function setPaymentChannelAttribute($value): void
    {
        $v = strtolower(trim((string)$value));
        // small alias map
        $map = ['mo-mo' => 'momo', 'mtn' => 'momo', 'mtn momo' => 'momo'];
        if (isset($map[$v])) $v = $map[$v];

        $this->attributes['payment_channel'] = in_array($v, self::CHANNELS, true) ? $v : 'cash';
    }

    /** Quick check: fully paid? */
    public function getIsPaidAttribute(): bool
    {
        return ($this->status === 'completed') && ((float)$this->balance_due <= 0.009);
    }
public function returns()
{
    return $this->hasMany(\App\Models\PurchaseReturn::class);
}

public function returnItems()
{
    return $this->hasManyThrough(
        \App\Models\PurchaseReturnItem::class,
        \App\Models\PurchaseReturn::class,
        'purchase_id', 'purchase_return_id'
    );
}
    /* ───────────── Helpers ───────────── */

    /** Recalculate subtotal, total, and balance fields from items + existing tax/discount (currency values). */
    public function updateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('total_cost');
        $tax      = (float) ($this->tax ?? 0);
        $discount = (float) ($this->discount ?? 0);

        $this->subtotal     = round($subtotal, 2);
        $this->total_amount = round(($subtotal + $tax) - $discount, 2);
        $this->balance_due  = round(($this->total_amount ?? 0) - (float)($this->amount_paid ?? 0), 2);

        $this->save();
    }
}
