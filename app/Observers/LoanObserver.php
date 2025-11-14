<?php

namespace App\Observers;

use App\Models\{Loan, Transaction, DebitCredit};
use Illuminate\Support\Facades\{Log, Auth};

class LoanObserver
{
    public function created(Loan $loan): void
    {
        try {
            $isGiven = $loan->type === 'given';   // we lent out money → debit cash
            $isTaken = $loan->type === 'taken';   // we took a loan → credit cash

            // Create the initial disbursement/receipt transaction and tie it to the loan
            $transaction = Transaction::create([
                'type'             => $isGiven ? 'debit' : 'credit',
                'user_id'          => Auth::id(),
                'customer_id'      => $loan->customer_id,
                'supplier_id'      => $loan->supplier_id,
                'loan_id'          => $loan->id,               // ✅ critical
                'amount'           => $loan->amount,
                'transaction_date' => $loan->loan_date ?? now(),
                'method'           => 'cash',
                'notes'            => ucfirst($loan->type) . " loan recorded - #{$loan->id}",
            ]);

            DebitCredit::create([
                'type'           => $isGiven ? 'debit' : 'credit',
                'amount'         => $loan->amount,
                'description'    => ucfirst($loan->type) . " loan recorded - #{$loan->id}",
                'date'           => ($loan->loan_date ?? now())->toDateString(),
                'user_id'        => Auth::id(),
                'customer_id'    => $loan->customer_id,
                'supplier_id'    => $loan->supplier_id,
                'transaction_id' => $transaction->id,
            ]);

            Log::info('✅ LoanObserver: created loan & financial records', [
                'loan_id'        => $loan->id,
                'transaction_id' => $transaction->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ LoanObserver@created failed', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function updated(Loan $loan): void
    {
        try {
            // Find the initial transaction via loan_id (not notes)
            $transaction = Transaction::where('loan_id', $loan->id)->first();

            if ($transaction) {
                // Keep base transaction in sync if the principal amount/type changed
                $transaction->update([
                    'type'             => $loan->type === 'given' ? 'debit' : 'credit',
                    'amount'           => $loan->amount,
                    'transaction_date' => $loan->loan_date ?? $transaction->transaction_date,
                    'customer_id'      => $loan->customer_id,
                    'supplier_id'      => $loan->supplier_id,
                    'notes'            => ucfirst($loan->type) . " loan updated - #{$loan->id}",
                ]);

                // Mirror the DebitCredit linked to that transaction (if any)
                $dc = DebitCredit::where('transaction_id', $transaction->id)->first();
                if ($dc) {
                    $dc->update([
                        'type'        => $loan->type === 'given' ? 'debit' : 'credit',
                        'amount'      => $loan->amount,
                        'description' => ucfirst($loan->type) . " loan updated - #{$loan->id}",
                        'date'        => ($loan->updated_at ?? now())->toDateString(),
                        'customer_id' => $loan->customer_id,
                        'supplier_id' => $loan->supplier_id,
                    ]);
                }
            }

            // Keep linked sale/purchase status in sync
            if ($loan->status === 'paid') {
                if ($loan->sale) {
                    $loan->sale->updateQuietly(['status' => 'completed']);
                }
                if ($loan->purchase) {
                    $loan->purchase->updateQuietly(['status' => 'completed']);
                }
            } elseif ($loan->status === 'pending') {
                if ($loan->sale) {
                    $loan->sale->updateQuietly(['status' => 'pending']);
                }
                if ($loan->purchase) {
                    $loan->purchase->updateQuietly(['status' => 'pending']);
                }
            }

            Log::info('🔁 LoanObserver: updated', ['loan_id' => $loan->id]);
        } catch (\Throwable $e) {
            Log::error('❌ LoanObserver@updated failed', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function deleted(Loan $loan): void
    {
        try {
            // Remove initial financial entries tied by loan_id
            $txns = Transaction::where('loan_id', $loan->id)->get();
            foreach ($txns as $t) {
                DebitCredit::where('transaction_id', $t->id)->delete();
                $t->delete();
            }

            Log::info('🗑️ LoanObserver: deleted linked records', ['loan_id' => $loan->id]);
        } catch (\Throwable $e) {
            Log::error('❌ LoanObserver@deleted failed', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
