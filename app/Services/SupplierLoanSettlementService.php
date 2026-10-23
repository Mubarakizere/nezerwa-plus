<?php

namespace App\Services;

use App\Models\{Sale, Supplier, Loan, LoanPayment};
use Illuminate\Support\Facades\Log;

class SupplierLoanSettlementService
{
    /**
     * Settle a supplier's pending taken loans FIFO (oldest loan first)
     * using the value of goods taken in a sale.
     *
     * @param Sale     $sale
     * @param Supplier $supplier
     * @param float    $offsetAmount
     * @param int|null $userId
     * @return array   Summary of settled loans
     */
    public function settleFifo(Sale $sale, Supplier $supplier, float $offsetAmount, ?int $userId = null): array
    {
        $userId = $userId ?: ($sale->user_id ?? auth()->id());
        $remainingToOffset = round($offsetAmount, 2);
        $settledRecords = [];

        if ($remainingToOffset <= 0.005) {
            return $settledRecords;
        }

        // Fetch all pending 'taken' loans for this supplier in FIFO order
        $pendingLoans = $supplier->pendingTakenLoans()
            ->withSum('payments', 'amount')
            ->orderBy('loan_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($pendingLoans as $loan) {
            if ($remainingToOffset <= 0.005) {
                break;
            }

            $alreadyPaid = (float) ($loan->payments_sum_amount ?? $loan->payments()->sum('amount'));
            $loanRemaining = max(0.0, round((float)$loan->amount - $alreadyPaid, 2));

            if ($loanRemaining <= 0.005) {
                continue;
            }

            $allocated = min($remainingToOffset, $loanRemaining);

            $payment = LoanPayment::create([
                'loan_id'      => $loan->id,
                'sale_id'      => $sale->id,
                'user_id'      => $userId,
                'amount'       => $allocated,
                'payment_date' => $sale->sale_date ?? now(),
                'method'       => 'supplier_offset',
                'notes'        => "Goods exchange: Sale #{$sale->id}",
            ]);

            $newTotalPaid = $alreadyPaid + $allocated;
            if ($newTotalPaid >= ((float)$loan->amount - 0.01)) {
                $loan->update(['status' => 'paid']);
            }

            $settledRecords[] = [
                'loan_id'   => $loan->id,
                'allocated' => $allocated,
                'fully_paid'=> $newTotalPaid >= ((float)$loan->amount - 0.01),
            ];

            $remainingToOffset = round($remainingToOffset - $allocated, 2);

            Log::info('📦 Supplier loan partially/fully settled by goods exchange', [
                'sale_id'     => $sale->id,
                'loan_id'     => $loan->id,
                'supplier_id' => $supplier->id,
                'allocated'   => $allocated,
                'remaining'   => $remainingToOffset,
            ]);
        }

        return $settledRecords;
    }

    /**
     * Reverse any existing loan payments made for a specific sale
     * (used during sale update or cancellation).
     *
     * @param Sale $sale
     * @return void
     */
    public function reverseForSale(Sale $sale): void
    {
        $existingPayments = LoanPayment::where('sale_id', $sale->id)->get();

        foreach ($existingPayments as $payment) {
            $loan = $payment->loan;
            $payment->delete();

            if ($loan) {
                // Re-evaluate loan status
                $remainingPaid = (float) $loan->payments()->sum('amount');
                if ($remainingPaid < ((float)$loan->amount - 0.01)) {
                    $loan->update(['status' => 'pending']);
                }
            }
        }
    }
}
