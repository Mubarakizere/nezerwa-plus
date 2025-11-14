<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'sale_date'  => 'date',
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
     * Accessors / helpers
     */
    public function getBalanceAttribute(): float
    {
        return (float) (($this->total_amount ?? 0) - ($this->amount_paid ?? 0));
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
    public function payments() { return $this->hasMany(\App\Models\SalePayment::class); }

}
