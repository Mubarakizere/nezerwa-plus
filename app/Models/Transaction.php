<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'user_id',
        'customer_id',
        'supplier_id',
        'sale_id',
        'purchase_id',
        'amount',
        'transaction_date',
        'method',
        'notes',
        'loan_id',
    ];

    // Who recorded the transaction
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // If related to a customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // If related to a supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // If transaction comes from a sale
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    // If transaction comes from a purchase
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
    public function loan()
    {
        return $this->belongsTo(\App\Models\Loan::class);
    }

    /**
     * Check if this is a "Send to Boss" withdrawal.
     */
    public function getIsWithdrawalAttribute()
    {
        return str_contains($this->notes ?? '', 'Withdrawal (Send to Boss)');
    }

    /**
     * Check if this is an internal transfer.
     */
    public function getIsTransferAttribute()
    {
        return str_contains($this->notes ?? '', 'Transfer OUT to') 
            || str_contains($this->notes ?? '', 'Transfer IN from');
    }
}
