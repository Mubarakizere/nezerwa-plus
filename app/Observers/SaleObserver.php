<?php

namespace App\Observers;

use App\Models\{Sale, Transaction, DebitCredit, Loan};
use Illuminate\Support\Facades\{Auth, DB, Log};

class SaleObserver
{
    /**
     * 🔹 When a Sale is created
     * Automatically creates related financial and loan records.
     */
    public function created(Sale $sale)
    {
        DB::afterCommit(function () use ($sale) {
            try {
                Log::info('🧾 SaleObserver: created()', ['sale_id' => $sale->id]);

                // 1️⃣ Record financials only if something was paid
                if ($sale->amount_paid > 0) {
                    $transaction = Transaction::create([
                        'type'             => 'credit',
                        'user_id'          => $sale->user_id ?? Auth::id(),
                        'customer_id'      => $sale->customer_id,
                        'sale_id'          => $sale->id,
                        'amount'           => $sale->amount_paid,
                        'transaction_date' => $sale->sale_date,
                        'method'           => $sale->method ?? 'cash',
                        'notes'            => "Auto-generated from Sale #{$sale->id}",
                    ]);

                    DebitCredit::create([
                        'type'           => 'credit',
                        'amount'         => $sale->amount_paid,
                        'description'    => "Sale recorded – Invoice #{$sale->id}",
                        'date'           => now()->toDateString(),
                        'user_id'        => $sale->user_id ?? Auth::id(),
                        'customer_id'    => $sale->customer_id,
                        'transaction_id' => $transaction->id,
                    ]);
                }

                // 2️⃣ Auto-create Loan if there’s an unpaid balance
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
                    Log::info('💰 SaleObserver: auto-loan created', ['sale_id' => $sale->id, 'unpaid' => $unpaid]);
                } else {
                    $sale->updateQuietly(['status' => 'completed']);
                }

            } catch (\Throwable $e) {
                Log::error('❌ SaleObserver: creation failed', [
                    'sale_id' => $sale->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * 🔹 When a Sale is updated
     * Keeps financial + loan records in sync.
     */
    public function updated(Sale $sale)
    {
        DB::afterCommit(function () use ($sale) {
            try {
                Log::info('♻️ SaleObserver: updated()', ['sale_id' => $sale->id]);

                $unpaid = ($sale->total_amount ?? 0) - ($sale->amount_paid ?? 0);
                $loan   = Loan::where('sale_id', $sale->id)->first();

                // ✅ Fully paid
                if ($unpaid <= 0.009) {
                    if ($loan && $loan->status !== 'paid') {
                        $loan->update(['status' => 'paid']);
                    }
                    $sale->updateQuietly(['status' => 'completed']);
                    Log::info('✅ Sale + Loan marked as paid', ['sale_id' => $sale->id]);
                }

                // ✅ Still owes
                elseif ($unpaid > 0.009) {
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
                    Log::info('💸 SaleObserver: loan pending', ['sale_id' => $sale->id, 'unpaid' => $unpaid]);
                }

                // ✅ Sync transaction (only if exists)
                if ($sale->transaction) {
                    $sale->transaction->update([
                        'amount' => $sale->amount_paid ?? 0,
                        'notes'  => "Updated from Sale #{$sale->id}",
                    ]);
                }

            } catch (\Throwable $e) {
                Log::error('❌ SaleObserver: update failed', [
                    'sale_id' => $sale->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * 🔹 When a Sale is deleted
     */
    public function deleted(Sale $sale)
    {
        DB::afterCommit(function () use ($sale) {
            try {
                Loan::where('sale_id', $sale->id)->delete();
                DebitCredit::whereHas('transaction', fn($q) =>
                    $q->where('sale_id', $sale->id)
                )->delete();
                $sale->transaction?->delete();

                Log::info('🗑️ SaleObserver: cleaned up', ['sale_id' => $sale->id]);
            } catch (\Throwable $e) {
                Log::error('❌ SaleObserver: delete failed', [
                    'sale_id' => $sale->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        });
    }
}
