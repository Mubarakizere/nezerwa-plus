<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemLoan extends Model
{
    protected $fillable = [
        'partner_id',
        'product_id',
        'direction',          // 'given' | 'taken'
        'item_name',
        'unit',
        'quantity',
        'loan_date',
        'due_date',
        'quantity_returned',
        'status',             // pending | partial | returned | overdue
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity'           => 'decimal:2',
        'quantity_returned'  => 'decimal:2',
        'loan_date'          => 'date',
        'due_date'           => 'date',
    ];

    // Relationships
    public function partner(): BelongsTo
    {
        return $this->belongsTo(PartnerCompany::class, 'partner_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ItemLoanReturn::class, 'item_loan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Accessors
    public function remaining(): Attribute
    {
        return Attribute::get(function () {
            $q  = (float)$this->quantity;
            $qr = (float)$this->quantity_returned;
            $rem = $q - $qr;
            return $rem > 0 ? round($rem, 2) : 0.00;
        });
    }

    public function isOverdue(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->due_date) return false;
            return $this->remaining > 0 && Carbon::parse($this->due_date)->isBefore(today());
        });
    }

    /**
     * Compute and set status according to current fields.
     * - returned if remaining <= 0
     * - partial  if returned > 0 and remaining > 0
     * - pending  if returned = 0
     * - overdue  if due_date < today and remaining > 0 (overrides others)
     */
    public function refreshStatus(): void
    {
        $remaining = (float)$this->remaining;
        $returned  = (float)$this->quantity_returned;

        if ($remaining <= 0.0) {
            $this->status = 'returned';
        } elseif ($returned > 0.0) {
            $this->status = 'partial';
        } else {
            $this->status = 'pending';
        }

        if ($this->due_date && $remaining > 0 && Carbon::parse($this->due_date)->isBefore(today())) {
            $this->status = 'overdue';
        }
    }
}
