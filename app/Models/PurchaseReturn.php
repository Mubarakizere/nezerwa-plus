<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $fillable = [
        'purchase_id','supplier_id','user_id',
        'return_date','payment_channel','method','notes',
        'total_amount','refund_amount','status'
    ];

    protected $casts = [
        'return_date'   => 'date',
        'total_amount'  => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    public function purchase(){ return $this->belongsTo(Purchase::class); }
    public function supplier(){ return $this->belongsTo(Supplier::class); }
    public function user(){ return $this->belongsTo(User::class); }
    public function items(){ return $this->hasMany(PurchaseReturnItem::class); }
}
