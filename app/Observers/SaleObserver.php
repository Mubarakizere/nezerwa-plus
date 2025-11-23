<?php

namespace App\Observers;

use App\Models\{Sale, Transaction, DebitCredit, Loan};
use Illuminate\Support\Facades\{Auth, DB, Log};

class SaleObserver
{
    /** Normalize channel from sale; fallback to 'cash'. */
    protected function channel(Sale $sale): string
    {
        $ch = strtolower((string)($sale->payment_channel ?? ''));
        return in_array($ch, ['cash', 'bank', 'momo', 'mobile_money'], true) ? $ch : 'cash';
    }

    /** Optional external reference (POS ref / MoMo Txn / Cheque). */
    protected function reference(Sale $sale): ?string
    {
        $ref = trim((string)($sale->method ?? ''));
        return $ref !== '' ? $ref : null;
    }

    /**
     * When a Sale is created
     * - create Transaction + DebitCredit if amount_paid > 0
     * - create Loan if there is an unpaid balance
     */
    public function created(Sale $sale): void
    {
        DB::afterCommit(function () use ($sale) {
            try {
                Log::info('🧾 SaleObserver.created', ['sale_id' => $sale->id]);

                // 1) Financial record only if paid something
                if (($sale->amount_paid ?? 0) > 0) {
                    $notes = "Auto-generated from Sale #{$sale->id} (channel: " . strtoupper($this->channel($sale)) . ")";
                    if ($ref = $this->reference($sale)) {
                        $notes .= " • Ref: {$ref}";
                    }

                    $txn = Transaction::create([
                        'type'             => 'credit',
                        'user_id'          => $sale->user_id ?? Auth::id(),
                        'customer_id'      => $sale->customer_id,
                        'sale_id'          => $sale->id,
                        'amount'           => $sale->amount_paid,
                        'transaction_date' => $sale->sale_date,
                        // Store payment channel here:
                        'method'           => $this->channel($sale),
                        'notes'            => $notes,
                    ]);

                    DebitCredit::create([
                        'type'           => 'credit',
                        'amount'         => $sale->amount_paid,
                        'description'    => "Sale recorded – Invoice #{$sale->id}",
                        'date'           => now()->toDateString(),
                        'user_id'        => $sale->user_id ?? Auth::id(),
                        'customer_id'    => $sale->customer_id,
                        'transaction_id' => $txn->id,
                    ]);
                }

                // 2) Loan if there’s an unpaid balance
                $unpaid = ($sale->total_amount ?? 0) - ($sale->amount_paid ?? 0);
                if ($unpaid > 0.009) {
                    Loan::firstOrCreate(
                        ['sale_id' => $sale->id],
                        [
                            'user_id'     => $sale->user_id ?? Auth::id(),
                            'customer_id' => $sale->customer_id,
                            'type'        => 'given',
                            'amount'      => $unpaid,
                            'loan_date'   => $sale->sale_date,
                            'status'      => 'pending',
                            'notes'       => "Auto-created from Sale #{$sale->id}",
                        ]
                    );
                    Log::info('💰 SaleObserver: loan created', ['sale_id' => $sale->id, 'unpaid' => $unpaid]);
                } else {
                    $sale->updateQuietly(['status' => 'completed']);
                }
            } catch (\Throwable $e) {
                Log::error('❌ SaleObserver.created failed', ['sale_id' => $sale->id, 'error' => $e->getMessage()]);
            }
        });
    }

    /**
     * When a Sale is updated
     * - sync Loan status/amount
     * - sync Transaction + DebitCredit
     */
    public function updated(Sale $sale): void
    {
        DB::afterCommit(function () use ($sale) {
            try {
                Log::info('♻️ SaleObserver.updated', ['sale_id' => $sale->id]);

                $unpaid = ($sale->total_amount ?? 0) - ($sale->amount_paid ?? 0);
                $loan   = Loan::where('sale_id', $sale->id)->first();

                // Loan + Sale state
                if ($unpaid <= 0.009) {
                    if ($loan && $loan->status !== 'paid') {
                        $loan->update(['status' => 'paid']);
                    }
                    $sale->updateQuietly(['status' => 'completed']);
                } else {
                    Loan::updateOrCreate(
                        ['sale_id' => $sale->id],
                        [
                            'user_id'     => $sale->user_id ?? Auth::id(),
                            'customer_id' => $sale->customer_id,
                            'type'        => 'given',
                            'amount'      => $unpaid,
                            'loan_date'   => $sale->sale_date,
                            'status'      => 'pending',
                            'notes'       => "Auto-updated from Sale #{$sale->id}",
                        ]
                    );
                    $sale->updateQuietly(['status' => 'pending']);
                }

                // Transaction + DebitCredit sync
                $txn = $sale->transaction; // may be null
                $paidIsZero = (float)($sale->amount_paid ?? 0) <= 0.009;

                if ($paidIsZero) {
                    if ($txn) {
                        DebitCredit::where('transaction_id', $txn->id)->delete();
                        $txn->delete();
                        Log::info('🗑️ Removed txn (amount_paid=0)', ['sale_id' => $sale->id]);
                    }
                    return;
                }

                $notes = "Updated from Sale #{$sale->id} (channel: " . strtoupper($this->channel($sale)) . ")";
                if ($ref = $this->reference($sale)) {
                    $notes .= " • Ref: {$ref}";
                }

                if ($txn) {
                    $txn->update([
                        'amount'           => $sale->amount_paid,
                        'transaction_date' => $sale->sale_date,
                        'method'           => $this->channel($sale),
                        'notes'            => $notes,
                    ]);

                    $dc = DebitCredit::where('transaction_id', $txn->id)->first();
                    if ($dc) {
                        $dc->update([
                            'amount'      => $sale->amount_paid,
                            'description' => "Sale updated – Invoice #{$sale->id}",
                            'date'        => now()->toDateString(),
                            'user_id'     => $sale->user_id ?? Auth::id(),
                            'customer_id' => $sale->customer_id,
                        ]);
                    } else {
                        DebitCredit::create([
                            'type'           => 'credit',
                            'amount'         => $sale->amount_paid,
                            'description'    => "Sale updated – Invoice #{$sale->id}",
                            'date'           => now()->toDateString(),
                            'user_id'        => $sale->user_id ?? Auth::id(),
                            'customer_id'    => $sale->customer_id,
                            'transaction_id' => $txn->id,
                        ]);
                    }
                } else {
                    // Create missing txn (paid > 0)
                    $txn = Transaction::create([
                        'type'             => 'credit',
                        'user_id'          => $sale->user_id ?? Auth::id(),
                        'customer_id'      => $sale->customer_id,
                        'sale_id'          => $sale->id,
                        'amount'           => $sale->amount_paid,
                        'transaction_date' => $sale->sale_date,
                        'method'           => $this->channel($sale),
                        'notes'            => $notes,
                    ]);

                    DebitCredit::create([
                        'type'           => 'credit',
                        'amount'         => $sale->amount_paid,
                        'description'    => "Sale recorded – Invoice #{$sale->id}",
                        'date'           => now()->toDateString(),
                        'user_id'        => $sale->user_id ?? Auth::id(),
                        'customer_id'    => $sale->customer_id,
                        'transaction_id' => $txn->id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('❌ SaleObserver.updated failed', ['sale_id' => $sale->id, 'error' => $e->getMessage()]);
            }
        });
    }

    /**
     * When a Sale is deleted
     * - remove linked Loan, DebitCredit(s), and Transaction
     */
    public function deleted(Sale $sale): void
    {
        DB::afterCommit(function () use ($sale) {
            try {
                Loan::where('sale_id', $sale->id)->delete();

                DebitCredit::whereHas('transaction', fn ($q) =>
                    $q->where('sale_id', $sale->id)
                )->delete();

                $sale->transaction?->delete();

                Log::info('🗑️ SaleObserver.cleaned', ['sale_id' => $sale->id]);
            } catch (\Throwable $e) {
                Log::error('❌ SaleObserver.deleted failed', ['sale_id' => $sale->id, 'error' => $e->getMessage()]);
            }
        });
    }
}
