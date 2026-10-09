<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
    ];
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function pendingLoans()
    {
        return $this->hasMany(Loan::class)->where('type', 'given')->where('status', 'pending');
    }

    /**
     * Compute total outstanding debt across all pending loans given to this customer.
     * Efficiently uses eager-loaded relations when available.
     */
    public function getTotalDebtAttribute(): float
    {
        if ($this->relationLoaded('loans')) {
            return (float) $this->loans
                ->where('type', 'given')
                ->where('status', 'pending')
                ->sum(fn ($l) => (float) $l->remaining);
        }

        $pending = $this->loans()
            ->where('type', 'given')
            ->where('status', 'pending')
            ->withSum('payments', 'amount')
            ->get();

        return (float) $pending->sum(function ($l) {
            $paid = (float) ($l->payments_sum_amount ?? 0);
            return max(0, round((float) $l->amount - $paid, 2));
        });
    }

    /**
     * Count of pending loans.
     */
    public function getPendingLoansCountAttribute(): int
    {
        if ($this->relationLoaded('loans')) {
            return $this->loans->where('type', 'given')->where('status', 'pending')->count();
        }

        return $this->loans()->where('type', 'given')->where('status', 'pending')->count();
    }
}
