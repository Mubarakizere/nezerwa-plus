<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    use HasFactory;

    public const METHODS = ['cash','momo','bank','mobile_money'];

    protected $fillable = [
        'loan_id', 'user_id', 'amount', 'payment_date', 'method', 'notes'
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function loan() { return $this->belongsTo(Loan::class); }
    public function user() { return $this->belongsTo(User::class); }
}
