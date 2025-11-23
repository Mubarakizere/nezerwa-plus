<?php

namespace App\Observers;

use App\Models\{Purchase, Transaction, DebitCredit, Loan};
use Illuminate\Support\Facades\{Auth, DB, Log, Schema};

class PurchaseObserver
{
    /** Normalize channel from purchase; fallback to 'cash'. */
    protected function channel(Purchase $purchase): string
    {
        $ch = strtolower((string)($purchase->payment_channel ?? ''));
        return in_array($ch, ['cash','bank','momo','mobile_money'], true) ? $ch : 'cash';
    }

    /** What we store in transactions.method (we keep the channel there). */
    protected function methodForTxn(Purchase $purchase): string
    {
        return $this->channel($purchase);
    }

    /** Safely set status if the purchases table has that column. */
    protected function setStatusIfPresent(Purchase $purchase, string $status): void
    {
        if (Schema::hasColumn('purchases', 'status')) {
            $purchase->updateQuietly(['status' => $status]);
        }
    }

    /**
     * When a Purchase is created:
     * - Create Transaction (debit) + DebitCredit (debit) if amount_paid > 0
     * - Create Loan (taken) if unpaid balance exists
     */
    public function created(Purchase $purchase)
    {
        DB::afterCommit(function () use ($purchase) {
            try {
                Log::info('🧾 PurchaseObserver: created()', ['purchase_id' => $purchase->id]);

                // 1) Transaction & DebitCredit (only if paid something)
                $amountPaid = (float)($purchase->amount_paid ?? 0);
                if ($amountPaid > 0.009) {
                    $notes = "Auto-generated from Purchase #{$purchase->id} (channel: " . strtoupper($this->channel($purchase)) . ")";
                    if (!empty($purchase->method)) {
                        $notes .= " • Ref: {$purchase->method}";
                    }

                    $txn = Transaction::create([
                        'type'             => 'debit', // expense
                        'user_id'          => $purchase->user_id ?? Auth::id(),
                        'supplier_id'      => $purchase->supplier_id ?? null, // ok if your schema supports it
                        'purchase_id'      => $purchase->id,
                        'amount'           => $amountPaid,
                        'transaction_date' => $purchase->purchase_date ?? now(),
                        'method'           => $this->methodForTxn($purchase), // store channel
                        'notes'            => $notes,
                    ]);

                    DebitCredit::create([
                        'type'           => 'debit',
                        'amount'         => $amountPaid,
                        'description'    => "Supplier payment – Purchase #{$purchase->id}",
                        'date'           => now()->toDateString(),
                        'user_id'        => $purchase->user_id ?? Auth::id(),
                        'supplier_id'    => $purchase->supplier_id ?? null,
                        'transaction_id' => $txn->id,
                    ]);
                }

                // 2) Loan (taken) if not fully paid
                $unpaid = (float)($purchase->total_amount ?? 0) - (float)($purchase->amount_paid ?? 0);
                if ($unpaid > 0.009) {
                    Loan::updateOrCreate(
                        ['purchase_id' => $purchase->id],
                        [
                            'user_id'     => $purchase->user_id ?? Auth::id(),
                            'supplier_id' => $purchase->supplier_id ?? null,
                            'type'        => 'taken',
                            'amount'      => $unpaid,
                            'loan_date'   => $purchase->purchase_date ?? now(),
                            'status'      => 'pending',
                            'notes'       => "Auto-created from Purchase #{$purchase->id}",
                        ]
                    );
                    $this->setStatusIfPresent($purchase, 'pending');
                    Log::info('💰 PurchaseObserver: auto-loan created (taken)', ['purchase_id' => $purchase->id, 'unpaid' => $unpaid]);
                } else {
                    $this->setStatusIfPresent($purchase, 'completed');
                }
            } catch (\Throwable $e) {
                Log::error('❌ PurchaseObserver: creation failed', [
                    'purchase_id' => $purchase->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * When a Purchase is updated:
     * - Keep loan status/amount in sync
     * - Keep transaction/debitcredit synced, including deleting when amount becomes 0
     */
    public function updated(Purchase $purchase)
    {
        DB::afterCommit(function () use ($purchase) {
            try {
                Log::info('♻️ PurchaseObserver: updated()', ['purchase_id' => $purchase->id]);

                $amountPaid = (float)($purchase->amount_paid ?? 0);
                $unpaid     = (float)($purchase->total_amount ?? 0) - $amountPaid;

                // Loan sync
                $loan = Loan::where('purchase_id', $purchase->id)->first();
                if ($unpaid <= 0.009) {
                    if ($loan && $loan->status !== 'paid') {
                        $loan->update(['status' => 'paid']);
                    }
                    $this->setStatusIfPresent($purchase, 'completed');
                    Log::info('✅ PurchaseObserver: loan marked paid', ['purchase_id' => $purchase->id]);
                } else {
                    Loan::updateOrCreate(
                        ['purchase_id' => $purchase->id],
                        [
                            'user_id'     => $purchase->user_id ?? Auth::id(),
                            'supplier_id' => $purchase->supplier_id ?? null,
                            'type'        => 'taken',
                            'amount'      => $unpaid,
                            'loan_date'   => $purchase->purchase_date ?? now(),
                            'status'      => 'pending',
                            'notes'       => "Auto-updated from Purchase #{$purchase->id}",
                        ]
                    );
                    $this->setStatusIfPresent($purchase, 'pending');
                    Log::info('💸 PurchaseObserver: loan pending', ['purchase_id' => $purchase->id, 'unpaid' => $unpaid]);
                }

                // Transaction & DebitCredit sync
                $txn = $purchase->transaction; // may be null

                if ($amountPaid <= 0.009) {
                    // remove existing txn/debitcredit if payment now zero
                    if ($txn) {
                        DebitCredit::where('transaction_id', $txn->id)->delete();
                        $txn->delete();
                        Log::info('🗑️ PurchaseObserver: removed transaction (amount_paid=0)', ['purchase_id' => $purchase->id]);
                    }
                } else {
                    $notes = "Updated from Purchase #{$purchase->id} (channel: " . strtoupper($this->channel($purchase)) . ")";
                    if (!empty($purchase->method)) {
                        $notes .= " • Ref: {$purchase->method}";
                    }

                    if ($txn) {
                        // update existing txn
                        $txn->update([
                            'amount'           => $amountPaid,
                            'transaction_date' => $purchase->purchase_date ?? now(),
                            'method'           => $this->methodForTxn($purchase),
                            'notes'            => $notes,
                        ]);

                        // keep DebitCredit 1:1 with txn
                        $dc = DebitCredit::where('transaction_id', $txn->id)->first();
                        if ($dc) {
                            $dc->update([
                                'amount'      => $amountPaid,
                                'description' => "Supplier payment – Purchase #{$purchase->id}",
                                'date'        => now()->toDateString(),
                                'user_id'     => $purchase->user_id ?? Auth::id(),
                                'supplier_id' => $purchase->supplier_id ?? null,
                            ]);
                        } else {
                            DebitCredit::create([
                                'type'           => 'debit',
                                'amount'         => $amountPaid,
                                'description'    => "Supplier payment – Purchase #{$purchase->id}",
                                'date'           => now()->toDateString(),
                                'user_id'        => $purchase->user_id ?? Auth::id(),
                                'supplier_id'    => $purchase->supplier_id ?? null,
                                'transaction_id' => $txn->id,
                            ]);
                        }
                    } else {
                        // create missing txn + dc
                        $txn = Transaction::create([
                            'type'             => 'debit',
                            'user_id'          => $purchase->user_id ?? Auth::id(),
                            'supplier_id'      => $purchase->supplier_id ?? null,
                            'purchase_id'      => $purchase->id,
                            'amount'           => $amountPaid,
                            'transaction_date' => $purchase->purchase_date ?? now(),
                            'method'           => $this->methodForTxn($purchase),
                            'notes'            => $notes,
                        ]);

                        DebitCredit::create([
                            'type'           => 'debit',
                            'amount'         => $amountPaid,
                            'description'    => "Supplier payment – Purchase #{$purchase->id}",
                            'date'           => now()->toDateString(),
                            'user_id'        => $purchase->user_id ?? Auth::id(),
                            'supplier_id'    => $purchase->supplier_id ?? null,
                            'transaction_id' => $txn->id,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('❌ PurchaseObserver: update failed', [
                    'purchase_id' => $purchase->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * When a Purchase is deleted:
     * - Delete linked loan
     * - Delete debitcredits tied to its transaction
     * - Delete the transaction itself
     */
    public function deleted(Purchase $purchase)
    {
        DB::afterCommit(function () use ($purchase) {
            try {
                Loan::where('purchase_id', $purchase->id)->delete();

                DebitCredit::whereHas('transaction', function ($q) use ($purchase) {
                    $q->where('purchase_id', $purchase->id);
                })->delete();

                $purchase->transaction?->delete();

                Log::info('🗑️ PurchaseObserver: cleaned up', ['purchase_id' => $purchase->id]);
            } catch (\Throwable $e) {
                Log::error('❌ PurchaseObserver: delete failed', [
                    'purchase_id' => $purchase->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        });
    }
}
