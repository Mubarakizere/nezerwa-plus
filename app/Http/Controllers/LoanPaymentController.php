<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Transaction;
use App\Models\DebitCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LoanPaymentController extends Controller
{
    /**
     * Show the form to add a new payment for a loan.
     */
    public function create(Loan $loan)
    {
        //  Prevent adding payments to already paid loans
        if ($loan->status === 'paid') {
            return redirect()
                ->route('loans.show', $loan)
                ->with('error', 'This loan is already marked as paid. You cannot add more payments.');
        }

        return view('loan_payments.create', compact('loan'));
    }

    /**
     * Store a new loan payment.
     */
    public function store(Request $request, Loan $loan)
    {
        //  Prevent accidental payments if loan is already closed
        if ($loan->status === 'paid') {
            return redirect()
                ->route('loans.show', $loan)
                ->with('error', 'Cannot record payment — this loan is already marked as paid.');
        }

        $validated = $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'method'       => 'required|string|max:100',
            'notes'        => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $loan) {
            $userId = Auth::id();

            //  Record payment
            $payment = $loan->payments()->create([
                'user_id'      => $userId,
                'amount'       => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'method'       => $validated['method'],
                'notes'        => $validated['notes'] ?? null,
            ]);

            //  Create Transaction
            $transaction = Transaction::create([
                'type'             => 'credit',
                'amount'           => $payment->amount,
                'transaction_date' => now(),
                'method'           => $payment->method,
                'user_id'          => $userId,
                'customer_id'      => $loan->customer_id,
                'loan_id'          => $loan->id,
                'notes'            => 'Loan payment for Loan #' . $loan->id,
            ]);

            // Record in DebitCredit
            DebitCredit::create([
                'type'           => 'credit',
                'amount'         => $payment->amount,
                'description'    => 'Loan payment for Loan #' . $loan->id,
                'date'           => now()->toDateString(),
                'user_id'        => $userId,
                'customer_id'    => $loan->customer_id,
                'transaction_id' => $transaction->id,
            ]);

            //  Update Loan Status
            $totalPaid = $loan->payments()->sum('amount');
            $remaining = $loan->amount - $totalPaid;

            if ($remaining <= 0.009) {
                $loan->update(['status' => 'paid']);
            }
        });

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Payment recorded successfully.');
    }
}
