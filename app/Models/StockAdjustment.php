<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Relationship to the user who performed the adjustment
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship to the movement (polymorphic)
    public function stock_movement()
    {
        return $this->morphOne(StockMovement::class, 'source');
    }
}
