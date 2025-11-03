<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

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
    ];

    /* ───────────── Casts ───────────── */
    protected $casts = [
        'purchase_date' => 'date',
        'subtotal'      => 'float',
        'tax'           => 'float',
        'discount'      => 'float',
        'total_amount'  => 'float',
        'amount_paid'   => 'float',
        'balance_due'   => 'float',
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

    /* ───────────── Helpers ───────────── */

    /**
     * Recalculate subtotal, total, and balance fields.
     */
    public function updateTotals(): void
    {
        $subtotal = $this->items->sum('total_cost');
        $this->subtotal = $subtotal;
        $this->total_amount = ($subtotal + ($this->tax ?? 0)) - ($this->discount ?? 0);
        $this->balance_due = ($this->total_amount ?? 0) - ($this->amount_paid ?? 0);
        $this->save();
    }

    /**
     * Quick check: is this purchase fully paid?
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'completed' && ($this->balance_due ?? 0) <= 0.009;
    }
}
