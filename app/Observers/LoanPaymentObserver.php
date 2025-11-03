<?php

namespace App\Observers;

use App\Models\LoanPayment;
use Illuminate\Support\Facades\Log;

class LoanPaymentObserver
{
    /**
     * Handle creation of a loan payment.
     */
    public function created(LoanPayment $payment)
    {
        $this->recalculateLoanStatus($payment);
    }

    /**
     * Handle updates to a loan payment.
     */
    public function updated(LoanPayment $payment)
    {
        $this->recalculateLoanStatus($payment);
    }

    /**
     * Handle deletion of a loan payment.
     */
    public function deleted(LoanPayment $payment)
    {
        $this->recalculateLoanStatus($payment);
    }

    /**
     * 🔁 Centralized logic for recalculating and syncing loan + related records.
     * Supports full or partial (installment) payments.
     */
    protected function recalculateLoanStatus(LoanPayment $payment): void
    {
        $loan = $payment->loan;
        if (!$loan) {
            Log::warning("⚠️ LoanPaymentObserver: Payment {$payment->id} has no related loan.");
            return;
        }

        // 📊 Recalculate totals
        $totalPaid = (float) $loan->payments()->sum('amount');
        $remaining = round(($loan->amount ?? 0) - $totalPaid, 2);

        // ✅ If fully paid
        if ($remaining <= 0.009) {
            $loan->updateQuietly([
                'status' => 'paid',
            ]);

            Log::info("✅ Loan #{$loan->id} marked as PAID automatically (Total Paid: {$totalPaid}).");

            // --- Auto-update linked SALE ---
            if ($loan->sale) {
                $loan->sale->updateQuietly([
                    'status'       => 'completed',
                    'amount_paid'  => $loan->sale->total_amount,
                ]);
                Log::info("💰 Sale #{$loan->sale->id} marked COMPLETED (Loan #{$loan->id} fully paid).");
            }

            // --- Auto-update linked PURCHASE ---
            if ($loan->purchase) {
                $loan->purchase->updateQuietly([
                    'status'        => 'completed', // ✅ fix enum constraint
                    'balance_due'   => 0,
                    'amount_paid'   => $loan->purchase->total_amount,
                ]);
                Log::info("📦 Purchase #{$loan->purchase->id} marked COMPLETED and balance set to 0 (Loan #{$loan->id} fully paid).");
            }
        }

        // 🔁 If partially paid (installments)
        elseif ($remaining > 0.009) {
            $loan->updateQuietly([
                'status' => 'pending',
            ]);

            Log::info("💳 Loan #{$loan->id} updated (Installment Paid). Remaining: {$remaining}");

            // --- Reflect real-time remaining balances on related SALE ---
            if ($loan->sale) {
                $loan->sale->updateQuietly([
                    'status'       => 'pending',
                    'amount_paid'  => $totalPaid,
                ]);
                Log::info("↩️ Sale #{$loan->sale->id} updated (Partial payment, remaining: {$remaining}).");
            }

            // --- Reflect real-time remaining balances on related PURCHASE ---
            if ($loan->purchase) {
                $loan->purchase->updateQuietly([
                    'status'        => 'pending',
                    'amount_paid'   => $totalPaid,
                    'balance_due'   => $remaining,
                ]);
                Log::info("↩️ Purchase #{$loan->purchase->id} updated (Partial payment, remaining: {$remaining}).");
            }
        }
    }
}
