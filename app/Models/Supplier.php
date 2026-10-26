<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = ['name','email','phone','address'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Pending loans of type 'taken' (debts the store owes to this supplier).
     * Ordered FIFO (oldest loan date and ID first).
     */
    public function pendingTakenLoans()
    {
        return $this->hasMany(Loan::class)
            ->where('type', 'taken')
            ->where('status', 'pending')
            ->orderBy('loan_date', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Total amount the store currently owes this supplier across all pending taken loans.
     */
    public function getTotalOwedAttribute(): float
    {
        if ($this->relationLoaded('loans')) {
            return (float) $this->loans
                ->where('type', 'taken')
                ->where('status', 'pending')
                ->sum(fn ($l) => (float) $l->remaining);
        }

        $pending = $this->pendingTakenLoans()
            ->withSum('payments', 'amount')
            ->get();

        return (float) $pending->sum(function ($l) {
            $paid = (float) ($l->payments_sum_amount ?? 0);
            return max(0.0, round((float) $l->amount - $paid, 2));
        });
    }

    /**
     * Count of pending taken loans.
     */
    public function getPendingTakenLoansCountAttribute(): int
    {
        if ($this->relationLoaded('loans')) {
            return $this->loans->where('type', 'taken')->where('status', 'pending')->count();
        }

        return $this->pendingTakenLoans()->count();
    }
}

