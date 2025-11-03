<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',           // given | taken
        'customer_id',
        'supplier_id',
        'sale_id',
        'purchase_id',
        'amount',
        'loan_date',
        'due_date',
        'status',         // pending | paid
        'notes',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'due_date'  => 'date',
    ];

    /* ======================
     |  🔗 RELATIONSHIPS
     |====================== */

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /* ======================
     |  📊 HELPERS
     |====================== */

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getRemainingAttribute(): float
    {
        return round(($this->amount ?? 0) - ($this->total_paid ?? 0), 2);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    public function markAsPaid(): void
    {
        if ($this->status !== 'paid') {
            $this->update(['status' => 'paid']);
            Log::info("✅ Loan #{$this->id} marked as paid automatically.");
        }
    }

    public function checkIfFullyPaid(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        if ($totalPaid >= ($this->amount ?? 0) && $this->status !== 'paid') {
            $this->markAsPaid();
        }
    }
}
