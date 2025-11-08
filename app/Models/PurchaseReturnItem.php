<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id','purchase_item_id','product_id',
        'quantity','unit_cost','total_cost'
    ];
     protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_cost'  => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];
    public function return(){ return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id'); }
    public function purchaseItem(){ return $this->belongsTo(PurchaseItem::class); }
    public function product(){ return $this->belongsTo(Product::class); }
}
