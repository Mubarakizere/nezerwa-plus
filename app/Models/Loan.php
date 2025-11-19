<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class Loan extends Model
{
    use HasFactory;

    /** Allowed enums */
    public const TYPES    = ['given', 'taken'];
    public const STATUSES = ['pending', 'paid'];

    /** Any remaining within this tolerance is treated as zero */
    public const TOLERANCE = 0.01;

    protected $fillable = [
        'type',            // given | taken
        'customer_id',
        'supplier_id',
        'sale_id',
        'purchase_id',
        'amount',
        'loan_date',
        'due_date',
        'status',          // pending | paid
        'notes',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'loan_date' => 'date',
        'due_date'  => 'date',
    ];

    /** Expose handy virtuals in API/arrays */
    protected $appends = [
        'paid',
        'remaining',
        'is_overdue',
        'due_in_days',
    ];

    /* ======================
     |  🔗 RELATIONSHIPS
     |====================== */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /* ======================
     |  🧮 COMPUTED ATTRS
     |====================== */

    /**
     * Total paid so far. Uses eager aggregate if present:
     *   Loan::withSum('payments as paid','amount')
     */
    public function getPaidAttribute(): float
    {
        // When eager-loaded via withSum('payments as paid', 'amount')
        $agg = $this->getAttribute('paid');
        if ($agg !== null) {
            return (float) $agg;
        }

        // If relation already loaded
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount');
        }

        // Fallback: quick sum query
        return (float) $this->payments()->sum('amount');
    }

    /** Amount still due (clamped by tolerance to 0.00) */
    public function getRemainingAttribute(): float
    {
        $rem = (float) ($this->amount ?? 0) - $this->paid;
        if (abs($rem) < static::TOLERANCE) {
            return 0.0;
        }
        return round($rem, 2);
    }

    /** Whether this loan is currently overdue (pending + due_date in past + positive remaining) */
    public function getIsOverdueAttribute(): bool
    {
        $due = $this->due_date;
        return $this->status !== 'paid'
            && $due instanceof Carbon
            && $due->isPast()
            && $this->remaining > static::TOLERANCE;
    }

    /**
     * Days until due (negative if overdue). Null when no due date.
     * Example: -3 means due 3 days ago, 0 means due today, 7 means due in a week.
     */
    public function getDueInDaysAttribute(): ?int
    {
        return $this->due_date
            ? Carbon::today()->diffInDays($this->due_date, false)
            : null;
        // (false) keeps the sign (negative for past dates)
    }

    /* ======================
     |  🔎 SCOPES
     |====================== */

    public function scopeType(Builder $q, ?string $type): Builder
    {
        if ($type && in_array($type, self::TYPES, true)) {
            $q->where('type', $type);
        }
        return $q;
    }

    public function scopeStatus(Builder $q, ?string $status): Builder
    {
        if ($status && in_array($status, self::STATUSES, true)) {
            $q->where('status', $status);
        }
        return $q;
    }

    /**
     * Filter by date window on a given column (loan_date by default).
     * If $from > $to, it swaps them to be safe.
     */
    public function scopeBetween(Builder $q, $from = null, $to = null, string $column = 'loan_date'): Builder
    {
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }
        if ($from) {
            $q->whereDate($column, '>=', $from);
        }
        if ($to) {
            $q->whereDate($column, '<=', $to);
        }
        return $q;
    }

    /** Filter a specific calendar day on a chosen column (loan_date by default). */
    public function scopeDateOn(Builder $q, $date = null, string $column = 'loan_date'): Builder
    {
        if ($date) {
            $q->whereDate($column, '=', $date);
        }
        return $q;
    }

    /** Pending loans due within the next N days (inclusive). */
    public function scopeDueIn(Builder $q, ?int $days): Builder
    {
        if ($days === null) {
            return $q;
        }
        $start = Carbon::today();
        $end   = Carbon::today()->addDays($days);

        return $q->where('status', 'pending')
                 ->whereDate('due_date', '>=', $start)
                 ->whereDate('due_date', '<=', $end);
    }

    /** Pending & past-due loans. */
    public function scopeOverdue(Builder $q): Builder
    {
        return $q->where('status', 'pending')
                 ->whereDate('due_date', '<', Carbon::today());
    }

    /**
     * Match either side (customer or supplier) by ID.
     * Useful for a unified "party" filter.
     */
    public function scopeParty(Builder $q, ?int $partyId): Builder
    {
        if ($partyId) {
            $q->where(function ($w) use ($partyId) {
                $w->where('customer_id', $partyId)
                  ->orWhere('supplier_id', $partyId);
            });
        }
        return $q;
    }

    /** Free-text search across id/notes/customer/supplier. */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) {
            return $q;
        }

        $driver = $q->getModel()->getConnection()->getDriverName();
        $op     = $driver === 'pgsql' ? 'ilike' : 'like';
        $wild   = '%' . $term . '%';

        return $q->where(function ($w) use ($op, $wild, $term) {
            // If numeric term, allow direct id match
            if (is_numeric($term)) {
                $w->orWhere('id', (int) $term);
            }

            $w->orWhere('notes', $op, $wild)
              ->orWhereHas('customer', fn ($c) => $c->where('name', $op, $wild))
              ->orWhereHas('supplier', fn ($s) => $s->where('name', $op, $wild));
        });
    }

    /* ======================
     |  ✅ BUSINESS HELPERS
     |====================== */

    /** Mark as fully paid (status only; no dependency on extra columns). */
    public function markAsPaid(): void
    {
        if ($this->status !== 'paid') {
            $this->update(['status' => 'paid']);
            Log::info("✅ Loan #{$this->id} marked as paid.");
        }
    }
    public function transactions(){
    return $this->hasMany(\App\Models\Transaction::class);
}


    /** Auto-close when remaining <= tolerance. */
    public function syncPaidStatus(): void
    {
        if ($this->remaining <= static::TOLERANCE && $this->status !== 'paid') {
            $this->markAsPaid();
        }
    }

    /** Backward-compatible alias for older controller calls. */
    public function checkIfFullyPaid(): void
    {
        $this->syncPaidStatus();
    }
}
