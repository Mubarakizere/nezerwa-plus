<?php

namespace App\Observers;

use App\Models\{Purchase, Transaction, DebitCredit, Loan};
use Illuminate\Support\Facades\{Auth, DB, Log};

class PurchaseObserver
{
    /**
     * Handle "created" event for purchases.
     * Auto-create financial entries + loan if not fully paid.
     */
    public function created(Purchase $purchase)
    {
        DB::afterCommit(function () use ($purchase) {
            try {
                Log::info('🧾 PurchaseObserver: created()', ['purchase_id' => $purchase->id]);

                // ✅ 1. Record Transaction & DebitCredit
                if ($purchase->amount_paid > 0) {
                    $transaction = Transaction::create([
                        'type'             => 'debit', // because it's an expense
                        'user_id'          => $purchase->user_id ?? Auth::id(),
                        'supplier_id'      => $purchase->supplier_id,
                        'purchase_id'      => $purchase->id,
                        'amount'           => $purchase->amount_paid,
                        'transaction_date' => $purchase->purchase_date ?? now(),
                        'method'           => $purchase->method ?? 'cash',
                        'notes'            => 'Auto-generated from Purchase #' . $purchase->id,
                    ]);

                    DebitCredit::create([
                        'type'           => 'debit',
                        'amount'         => $purchase->amount_paid,
                        'description'    => 'Purchase payment - #' . $purchase->id,
                        'date'           => now()->toDateString(),
                        'user_id'        => $purchase->user_id ?? Auth::id(),
                        'supplier_id'    => $purchase->supplier_id,
                        'transaction_id' => $transaction->id,
                    ]);
                }

                // ✅ 2. Auto-create Loan (taken) if unpaid balance exists
                $unpaid = ($purchase->total_amount ?? 0) - ($purchase->amount_paid ?? 0);

                if ($unpaid > 0.009) {
                    Loan::updateOrCreate(
                        ['purchase_id' => $purchase->id],
                        [
                            'user_id'     => $purchase->user_id ?? Auth::id(),
                            'supplier_id' => $purchase->supplier_id,
                            'type'        => 'taken',
                            'amount'      => $unpaid,
                            'loan_date'   => $purchase->purchase_date ?? now(),
                            'status'      => 'pending',
                            'notes'       => 'Auto-created from Purchase #' . $purchase->id,
                        ]
                    );

                    Log::info('💰 PurchaseObserver: Auto-loan created (taken)', [
                        'purchase_id' => $purchase->id,
                        'unpaid'      => $unpaid,
                    ]);
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
     * Handle "updated" event for purchases.
     * Keeps loan + transaction in sync.
     */
    public function updated(Purchase $purchase)
    {
        DB::afterCommit(function () use ($purchase) {
            try {
                Log::info('♻️ PurchaseObserver: updated()', ['purchase_id' => $purchase->id]);

                $unpaid = ($purchase->total_amount ?? 0) - ($purchase->amount_paid ?? 0);
                $loan   = Loan::where('purchase_id', $purchase->id)->first();

                // ✅ Fully paid → mark loan + purchase as paid/completed
                if ($unpaid <= 0.009 && $loan) {
                    $loan->updateQuietly(['status' => 'paid']);
                    $purchase->updateQuietly(['status' => 'completed']);

                    Log::info('✅ PurchaseObserver: Loan + Purchase marked as paid', [
                        'purchase_id' => $purchase->id,
                        'loan_id'     => $loan->id,
                    ]);
                }

                // ✅ Still unpaid → update or create pending loan
                elseif ($unpaid > 0.009) {
                    Loan::updateOrCreate(
                        ['purchase_id' => $purchase->id],
                        [
                            'user_id'     => $purchase->user_id ?? Auth::id(),
                            'supplier_id' => $purchase->supplier_id,
                            'type'        => 'taken',
                            'amount'      => $unpaid,
                            'loan_date'   => $purchase->purchase_date ?? now(),
                            'status'      => 'pending',
                            'notes'       => 'Auto-updated from Purchase #' . $purchase->id,
                        ]
                    );

                    $purchase->updateQuietly(['status' => 'pending']);

                    Log::info('💸 PurchaseObserver: Loan updated (still pending)', [
                        'purchase_id' => $purchase->id,
                        'unpaid'      => $unpaid,
                    ]);
                }

                // ✅ Sync transaction amount if exists
                if ($purchase->transaction) {
                    $purchase->transaction->update([
                        'amount' => $purchase->amount_paid ?? 0,
                        'notes'  => 'Updated from Purchase #' . $purchase->id,
                    ]);
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
     * Handle "deleted" event for purchases.
     * Cleans up all linked financial data.
     */
    public function deleted(Purchase $purchase)
    {
        DB::afterCommit(function () use ($purchase) {
            try {
                Loan::where('purchase_id', $purchase->id)->delete();

                DebitCredit::whereHas('transaction', fn($q) =>
                    $q->where('purchase_id', $purchase->id)
                )->delete();

                $purchase->transaction?->delete();

                Log::info('🗑️ PurchaseObserver: cleaned up related loan + transaction', [
                    'purchase_id' => $purchase->id,
                ]);

            } catch (\Throwable $e) {
                Log::error('❌ PurchaseObserver: delete failed', [
                    'purchase_id' => $purchase->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        });
    }
}
