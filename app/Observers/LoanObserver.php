<?php

namespace App\Observers;

use App\Models\{Loan, Transaction, DebitCredit};
use Illuminate\Support\Facades\{Log, Auth};

class LoanObserver
{
    /**
     * 🔹 When a loan is created.
     */
    public function created(Loan $loan)
    {
        try {
            $isGiven = $loan->type === 'given'; // We gave money → Debit
            $isTaken = $loan->type === 'taken'; // We received money → Credit

            // 1️⃣ Create a Transaction record
            $transaction = Transaction::create([
                'type'             => $isGiven ? 'debit' : 'credit',
                'user_id'          => Auth::id() ?? 1,
                'customer_id'      => $loan->customer_id,
                'supplier_id'      => $loan->supplier_id,
                'amount'           => $loan->amount,
                'transaction_date' => $loan->loan_date,
                'method'           => 'cash',
                'notes'            => ucfirst($loan->type) . " loan recorded - #{$loan->id}",
            ]);

            // 2️⃣ Create DebitCredit entry linked to that transaction
            DebitCredit::create([
                'type'           => $isGiven ? 'debit' : 'credit',
                'amount'         => $loan->amount,
                'description'    => ucfirst($loan->type) . " loan recorded - #{$loan->id}",
                'date'           => $loan->loan_date,
                'user_id'        => Auth::id() ?? 1,
                'customer_id'    => $loan->customer_id,
                'supplier_id'    => $loan->supplier_id,
                'transaction_id' => $transaction->id,
            ]);

            Log::info('✅ LoanObserver: created loan & financial records', [
                'loan_id'        => $loan->id,
                'transaction_id' => $transaction->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ LoanObserver create failed', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🔹 When a loan is updated.
     */
    public function updated(Loan $loan)
    {
        try {
            // 1️⃣ Sync related debit/credit entry
            $debitCredit = DebitCredit::whereHas('transaction', function ($q) use ($loan) {
                $q->where('notes', 'like', "%#{$loan->id}%");
            })->first();

            if ($debitCredit) {
                $debitCredit->update([
                    'amount'      => $loan->amount,
                    'description' => ucfirst($loan->type) . " loan updated - #{$loan->id}",
                    'date'        => $loan->updated_at->toDateString(),
                ]);
            }

            // 2️⃣ If the loan is manually marked as PAID → update related sale/purchase
            if ($loan->status === 'paid') {
                if ($loan->sale) {
                    $loan->sale->updateQuietly(['status' => 'completed']);
                    Log::info("💰 Sale #{$loan->sale->id} marked COMPLETED (Loan #{$loan->id} paid).");
                }

                if ($loan->purchase) {
                    $loan->purchase->updateQuietly(['status' => 'completed']); // ✅ Fixed from 'paid' → 'completed'
                    Log::info("📦 Purchase #{$loan->purchase->id} marked COMPLETED (Loan #{$loan->id} paid).");
                }
            }

            // 3️⃣ If reverted back to pending → reflect on related sale/purchase
            elseif ($loan->status === 'pending') {
                if ($loan->sale) {
                    $loan->sale->updateQuietly(['status' => 'pending']);
                    Log::info("↩️ Sale #{$loan->sale->id} reverted to PENDING (Loan #{$loan->id}).");
                }

                if ($loan->purchase) {
                    $loan->purchase->updateQuietly(['status' => 'pending']);
                    Log::info("↩️ Purchase #{$loan->purchase->id} reverted to PENDING (Loan #{$loan->id}).");
                }
            }

            Log::info('🔁 LoanObserver: updated', ['loan_id' => $loan->id]);
        } catch (\Throwable $e) {
            Log::error('❌ LoanObserver update failed', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🔹 When a loan is deleted.
     */
    public function deleted(Loan $loan)
    {
        try {
            // Remove related financial entries
            DebitCredit::whereHas('transaction', function ($q) use ($loan) {
                $q->where('notes', 'like', "%#{$loan->id}%");
            })->delete();

            Transaction::where('notes', 'like', "%#{$loan->id}%")->delete();

            Log::info('🗑️ LoanObserver: deleted linked records', [
                'loan_id' => $loan->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ LoanObserver delete failed', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
