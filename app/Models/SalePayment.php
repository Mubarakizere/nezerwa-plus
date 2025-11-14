<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    protected $fillable = [
        'sale_id', 'method', 'amount', 'reference', 'phone', 'paid_at', 'user_id',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount'  => 'decimal:2',
    ];

    public function sale() { return $this->belongsTo(Sale::class); }
    public function user() { return $this->belongsTo(User::class); }
}
