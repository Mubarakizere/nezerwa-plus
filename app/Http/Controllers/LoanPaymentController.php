<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Transaction;
use App\Models\DebitCredit;
use App\Models\PaymentChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LoanPaymentController extends Controller
{
    /** Allow tiny rounding wiggle room when comparing remaining balance */
    private const EPSILON = 0.01;

    /**
     * Show the form to add a new payment for a loan.
     */
    public function create(Loan $loan)
    {
        // Block adding payments to already closed loans
        if ($loan->status === 'paid') {
            return redirect()
                ->route('loans.show', $loan)
                ->with('error', 'This loan is already marked as paid. You cannot add more payments.');
        }

        $paymentChannels = PaymentChannel::where('is_active', true)->get();

        return view('loan_payments.create', compact('loan', 'paymentChannels'));
    }

    /**
     * Store a new loan payment.
     */
    public function store(Request $request, Loan $loan)
{
    if ($loan->status === 'paid') {
        return redirect()->route('loans.show', $loan)
            ->with('error', 'Cannot record payment — this loan is already marked as paid.');
    }

    $validated = $request->validate([
        'amount'       => ['required','numeric','min:0.01'],
        'payment_date' => ['required','date'],
        'method'       => ['required','string'],
        'notes'        => ['nullable','string'],
    ]);

    try {
        DB::transaction(function () use ($validated, $loan) {
            $userId = Auth::id();

            $payment = $loan->payments()->create([
                'user_id'      => $userId,
                'amount'       => (float)$validated['amount'],
                'payment_date' => $validated['payment_date'],
                'method'       => strtolower(trim($validated['method'])),
                'notes'        => $validated['notes'] ?? null,
            ]);

            $txnType = $loan->type === 'taken' ? 'debit' : 'credit';

            $transaction = Transaction::create([
                'type'             => $txnType,
                'amount'           => $payment->amount,
                'transaction_date' => $validated['payment_date'],
                'method'           => $payment->method,
                'user_id'          => $userId,
                'customer_id'      => $loan->customer_id,
                'supplier_id'      => $loan->supplier_id,
                'loan_id'          => $loan->id, // ✅ relies on migration
                'notes'            => 'Loan payment for Loan #' . $loan->id,
            ]);

            DebitCredit::create([
                'type'           => $txnType,
                'amount'         => $payment->amount,
                'description'    => 'Loan payment for Loan #' . $loan->id,
                'date'           => $validated['payment_date'],
                'user_id'        => $userId,
                'customer_id'    => $loan->customer_id,
                'supplier_id'    => $loan->supplier_id,
                'transaction_id' => $transaction->id,
            ]);

            // Auto-close within epsilon
            $totalPaid = (float) $loan->payments()->sum('amount');
            if ($totalPaid >= ((float)$loan->amount - 0.01)) {
                $loan->update(['status' => 'paid']);
            }
        });

        return redirect()->route('loans.show', $loan)
            ->with('success', 'Payment recorded successfully.');
    } catch (\Throwable $e) {
        \Log::error('❌ LoanPayment@store failed', ['loan_id' => $loan->id, 'error' => $e->getMessage()]);
        return back()->withInput()->withErrors(['error' => 'Failed to record payment: '.$e->getMessage()]);
    }
}

}
