<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemLoanReturn extends Model
{
    protected $fillable = [
        'item_loan_id',
        'returned_qty',
        'return_date',
        'note',
        'user_id',
    ];

    protected $casts = [
        'returned_qty' => 'decimal:2',
        'return_date'  => 'date',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(ItemLoan::class, 'item_loan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
