<?php

namespace App\Observers;

use App\Models\LoanPayment;
use Illuminate\Support\Facades\Log;

class LoanPaymentObserver
{
    private const EPSILON = 0.01;

    public function created(LoanPayment $payment): void   { $this->syncLoan($payment); }
    public function updated(LoanPayment $payment): void   { $this->syncLoan($payment); }
    public function deleted(LoanPayment $payment): void   { $this->syncLoan($payment, true); }

    /**
     * Recompute totals and propagate state to linked sale/purchase.
     */
    private function syncLoan(LoanPayment $payment, bool $wasDeleted = false): void
    {
        $loan = $payment->loan()->with(['sale','purchase','payments'])->first();
        if (!$loan) {
            Log::warning(" LoanPaymentObserver: Payment {$payment->id} has no loan.");
            return;
        }

        $totalPaid = (float) $loan->payments->sum('amount');
        $remaining = round(($loan->amount ?? 0) - $totalPaid, 2);

        if ($remaining <= self::EPSILON) {
            $loan->updateQuietly(['status' => 'paid']);
            Log::info(" Loan #{$loan->id} auto-closed (paid={$totalPaid}).");

            if ($loan->sale) {
                $loan->sale->updateQuietly([
                    'status'      => 'completed',
                    'amount_paid' => $loan->sale->total_amount,
                ]);
            }
            if ($loan->purchase) {
                $loan->purchase->updateQuietly([
                    'status'      => 'completed',
                    'amount_paid' => $loan->purchase->total_amount,
                    'balance_due' => 0,
                ]);
            }
        } else {
            // still pending / partial
            $loan->updateQuietly(['status' => 'pending']);
            Log::info(" Loan #{$loan->id} partial (paid={$totalPaid}, remaining={$remaining}).");

            if ($loan->sale) {
                $loan->sale->updateQuietly([
                    'status'      => 'pending',
                    'amount_paid' => $totalPaid,
                ]);
            }
            if ($loan->purchase) {
                $loan->purchase->updateQuietly([
                    'status'      => 'pending',
                    'amount_paid' => $totalPaid,
                    'balance_due' => $remaining,
                ]);
            }
        }
    }
}
