<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebitCredit extends Model
{
    use HasFactory;

    protected $table = 'debits_credits';

    protected $fillable = [
        'type',
        'amount',
        'description',
        'date',
        'user_id',
        'customer_id',
        'supplier_id',
        'transaction_id',
    ];

    protected $casts = [
        'date'   => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    // ---- Relations
    public function user()       { return $this->belongsTo(User::class); }
    public function customer()   { return $this->belongsTo(Customer::class); }
    public function supplier()   { return $this->belongsTo(Supplier::class); }
    public function transaction(){ return $this->belongsTo(Transaction::class); }

    // ---- Scopes (make controller/views cleaner)
    public function scopeType($q, ?string $type)
    {
        return $type ? $q->where('type', $type) : $q;
    }

    public function scopeBetweenDates($q, ?string $from, ?string $to)
    {
        if ($from) $q->whereDate('date', '>=', $from);
        if ($to)   $q->whereDate('date', '<=', $to);
        return $q;
    }

    public function scopeSearch($q, ?string $term)
    {
        if (!$term) return $q;
        return $q->where('description', 'like', "%{$term}%");
    }
}
