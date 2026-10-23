<?php

namespace App\Observers;

use App\Models\{Sale, Transaction, DebitCredit, Loan};
use Illuminate\Support\Facades\{Auth, DB, Log};

class SaleObserver
{
    /** Normalize channel from sale; fallback to 'cash'. */
    /** Normalize channel from sale; fallback to 'cash'. */
    protected function channel(Sale $sale): string
    {
        // Allow dynamic slugs from database (e.g. zigama-css, equity-bank, etc.)
        $ch = strtolower((string)($sale->payment_channel ?? ''));
        return $ch ?: 'cash';
    }

    /** Optional external reference (POS ref / MoMo Txn / Cheque). */
    protected function reference(Sale $sale): ?string
    {
        $ref = trim((string)($sale->method ?? ''));
        return $ref !== '' ? $ref : null;
    }

    /**
     * When a Sale is created
     * - create Transaction + DebitCredit if amount_paid > 0 (monetary channels only)
     * - create Loan if there is an unpaid balance (Boss gifts NEVER create a loan)
     */
    public function created(Sale $sale): void
    {
        DB::afterCommit(function () use ($sale) {
            try {
                $sale->refresh();
                Log::info('🧾 SaleObserver.created', ['sale_id' => $sale->id]);

                // 🎁 Boss Gift: Do not create any loan or cash inflow transaction
                if ($sale->isGift()) {
                    Loan::where('sale_id', $sale->id)->delete();
                    if ($txn = $sale->transaction) {
                        DebitCredit::where('transaction_id', $txn->id)->delete();
                        $txn->delete();
                    }
                    $sale->updateQuietly(['status' => 'completed']);
                    Log::info('🎁 SaleObserver: Boss gift recorded, no loan/monetary txn created', ['sale_id' => $sale->id]);
                    return;
                }

                // Calculate monetary payments vs non-monetary coverage (gift or supplier loan offset)
                $nonMonetaryMethods = ['boss_gift', 'supplier_offset', 'goods_exchange'];
                $monetaryPaid       = 0.0;
                $nonMonetaryPaid    = 0.0;

                if ($sale->payments()->count() > 0) {
                    $monetaryPaid    = (float) $sale->payments()->whereNotIn('method', $nonMonetaryMethods)->sum('amount');
                    $nonMonetaryPaid = (float) $sale->payments()->whereIn('method', $nonMonetaryMethods)->sum('amount');
                } else {
                    if (in_array($this->channel($sale), $nonMonetaryMethods, true)) {
                        $nonMonetaryPaid = (float) ($sale->amount_paid ?? 0);
                    } else {
                        $monetaryPaid    = (float) ($sale->amount_paid ?? 0);
                    }
                }

                // 1) Financial record only if actual monetary amount paid
                if ($monetaryPaid > 0) {
                    $notes = "Auto-generated from Sale #{$sale->id} (channel: " . strtoupper($this->channel($sale)) . ")";
                    if ($ref = $this->reference($sale)) {
                        $notes .= " • Ref: {$ref}";
                    }

                    $txn = Transaction::create([
                        'type'             => 'credit',
                        'user_id'          => $sale->user_id ?? Auth::id(),
                        'customer_id'      => $sale->customer_id,
                        'sale_id'          => $sale->id,
                        'amount'           => $monetaryPaid,
                        'transaction_date' => $sale->sale_date,
                        'method'           => $this->channel($sale),
                        'notes'            => $notes,
                    ]);

                    DebitCredit::create([
                        'type'           => 'credit',
                        'amount'         => $monetaryPaid,
                        'description'    => "Sale recorded – Invoice #{$sale->id}",
                        'date'           => now()->toDateString(),
                        'user_id'        => $sale->user_id ?? Auth::id(),
                        'customer_id'    => $sale->customer_id,
                        'transaction_id' => $txn->id,
                    ]);
                }

                // 2) Loan only if there’s an unpaid balance (gift and offset amounts are not debt)
                $totalCovered = $monetaryPaid + $nonMonetaryPaid;
                $unpaid = max(0.0, ($sale->total_amount ?? 0) - $totalCovered);
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
                    Loan::where('sale_id', $sale->id)->delete();
                    $sale->updateQuietly(['status' => 'completed']);
                }
            } catch (\Throwable $e) {
                Log::error('❌ SaleObserver.created failed', ['sale_id' => $sale->id, 'error' => $e->getMessage()]);
            }
        });
    }

    /**
     * When a Sale is updated
     * - sync Loan status/amount (Boss gifts NEVER create a loan)
     * - sync Transaction + DebitCredit
     */
    public function updated(Sale $sale): void
    {
        DB::afterCommit(function () use ($sale) {
            try {
                $sale->refresh();
                Log::info('♻️ SaleObserver.updated', ['sale_id' => $sale->id]);

                // 🎁 Boss Gift: Do not maintain any loan or monetary transaction
                if ($sale->isGift()) {
                    Loan::where('sale_id', $sale->id)->delete();
                    if ($txn = $sale->transaction) {
                        DebitCredit::where('transaction_id', $txn->id)->delete();
                        $txn->delete();
                        Log::info('🗑️ Removed txn for boss gift', ['sale_id' => $sale->id]);
                    }
                    $sale->updateQuietly(['status' => 'completed']);
                    Log::info('🎁 SaleObserver: Boss gift updated, loan/txns cleaned', ['sale_id' => $sale->id]);
                    return;
                }

                // Calculate monetary payments vs non-monetary coverage
                $nonMonetaryMethods = ['boss_gift', 'supplier_offset', 'goods_exchange'];
                $monetaryPaid       = 0.0;
                $nonMonetaryPaid    = 0.0;

                if ($sale->payments()->count() > 0) {
                    $monetaryPaid    = (float) $sale->payments()->whereNotIn('method', $nonMonetaryMethods)->sum('amount');
                    $nonMonetaryPaid = (float) $sale->payments()->whereIn('method', $nonMonetaryMethods)->sum('amount');
                } else {
                    if (in_array($this->channel($sale), $nonMonetaryMethods, true)) {
                        $nonMonetaryPaid = (float) ($sale->amount_paid ?? 0);
                    } else {
                        $monetaryPaid    = (float) ($sale->amount_paid ?? 0);
                    }
                }

                $totalCovered = $monetaryPaid + $nonMonetaryPaid;
                $unpaid = max(0.0, ($sale->total_amount ?? 0) - $totalCovered);
                $loan   = Loan::where('sale_id', $sale->id)->first();

                // Loan + Sale state
                if ($unpaid <= 0.009) {
                    if ($loan) {
                        $loan->delete();
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

                // Transaction + DebitCredit sync (monetary only)
                $txn = $sale->transaction;
                $paidIsZero = $monetaryPaid <= 0.009;

                if ($paidIsZero) {
                    if ($txn) {
                        DebitCredit::where('transaction_id', $txn->id)->delete();
                        $txn->delete();
                        Log::info('🗑️ Removed txn (monetary_paid=0)', ['sale_id' => $sale->id]);
                    }
                    return;
                }

                $notes = "Updated from Sale #{$sale->id} (channel: " . strtoupper($this->channel($sale)) . ")";
                if ($ref = $this->reference($sale)) {
                    $notes .= " • Ref: {$ref}";
                }

                if ($txn) {
                    $txn->update([
                        'amount'           => $monetaryPaid,
                        'transaction_date' => $sale->sale_date,
                        'method'           => $this->channel($sale),
                        'notes'            => $notes,
                    ]);

                    $dc = DebitCredit::where('transaction_id', $txn->id)->first();
                    if ($dc) {
                        $dc->update([
                            'amount'      => $monetaryPaid,
                            'description' => "Sale updated – Invoice #{$sale->id}",
                            'date'        => now()->toDateString(),
                            'user_id'     => $sale->user_id ?? Auth::id(),
                            'customer_id' => $sale->customer_id,
                        ]);
                    } else {
                        DebitCredit::create([
                            'type'           => 'credit',
                            'amount'         => $monetaryPaid,
                            'description'    => "Sale updated – Invoice #{$sale->id}",
                            'date'           => now()->toDateString(),
                            'user_id'        => $sale->user_id ?? Auth::id(),
                            'customer_id'    => $sale->customer_id,
                            'transaction_id' => $txn->id,
                        ]);
                    }
                } else {
                    // Create missing txn (monetary paid > 0)
                    $txn = Transaction::create([
                        'type'             => 'credit',
                        'user_id'          => $sale->user_id ?? Auth::id(),
                        'customer_id'      => $sale->customer_id,
                        'sale_id'          => $sale->id,
                        'amount'           => $monetaryPaid,
                        'transaction_date' => $sale->sale_date,
                        'method'           => $this->channel($sale),
                        'notes'            => $notes,
                    ]);

                    DebitCredit::create([
                        'type'           => 'credit',
                        'amount'         => $monetaryPaid,
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
