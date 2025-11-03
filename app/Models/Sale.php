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
        'method',
        'status',
        'notes',
    ];

    // ✅ Date casting
    protected $casts = [
        'sale_date'  => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 🧩 Relationships
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

    // 📊 Accessor for balance due
    public function getBalanceAttribute()
    {
        return ($this->total_amount ?? 0) - ($this->amount_paid ?? 0);
    }

    // 📈 Profit summary for reports
    public function totalProfit()
    {
        return $this->items->sum('profit');
    }
}
