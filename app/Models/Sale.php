<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Sale extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'customer_id',
        'user_id',
        'sale_date',
        'total_amount',
        'amount_paid',
        'payment_channel',
        'method',
        'status',
        'notes',
        'is_gift',
        'gift_reason',
    ];

    protected $casts = [
        'sale_date'  => 'date',
        'is_gift'    => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    /**
     * Linked loan created to cover this sale's balance.
     * (Loan belongsTo Sale; Sale hasOne Loan)
     */
    public function loan()
    {
        return $this->hasOne(Loan::class);
    }

    /**
     * Check if this sale is a complimentary gift given by the boss
     */
    public function isGift(): bool
    {
        return (bool) ($this->is_gift || strtolower((string)($this->payment_channel ?? '')) === 'boss_gift');
    }

    /**
     * Scope for gift sales
     */
    public function scopeGifts($query)
    {
        return $query->where(function ($q) {
            $q->where('is_gift', true)
              ->orWhere('payment_channel', 'boss_gift');
        });
    }

    /**
     * Scope for non-gift (regular) sales
     */
    public function scopeNonGifts($query)
    {
        return $query->where('is_gift', false)
                     ->where('payment_channel', '!=', 'boss_gift');
    }

    /**
     * Accessors / helpers
     */
    public function getBalanceAttribute(): float
    {
        if ($this->isGift()) {
            return 0.0;
        }

        // Account for any partial boss_gift split payments
        $giftPaid = 0.0;
        if ($this->relationLoaded('payments')) {
            $giftPaid = (float) $this->payments->where('method', 'boss_gift')->sum('amount');
        }

        $effectiveTotal = max(0.0, (float)($this->total_amount ?? 0) - $giftPaid);
        $paid = (float) ($this->amount_paid ?? 0);

        return max(0.0, round($effectiveTotal - $paid, 2));
    }

    public function totalProfit(): float
    {
        return (float) $this->items->sum('profit');
    }

    /**
     * Net total after subtracting returns.
     * Uses preloaded `returns_total` if present, otherwise queries.
     */
    public function getNetTotalAttribute(): float
    {
        $returns = (float) ($this->returns_total ?? $this->returns()->sum('amount'));
        return max(0.0, (float) ($this->total_amount ?? 0) - $returns);
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\SalePayment::class);
    }

}
