<?php

namespace App\Http\Controllers;

use App\Models\{Customer, Loan, LoanPayment, Transaction, DebitCredit, PaymentChannel};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log};

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query  = trim((string) $request->get('q', ''));
        $filter = trim((string) $request->get('filter', 'all')); // all, debt, clear

        $customersQuery = Customer::query()
            ->with([
                'loans' => function ($q) {
                    $q->where('type', 'given')
                      ->where('status', 'pending')
                      ->withSum('payments', 'amount');
                }
            ]);

        if ($query !== '') {
            $customersQuery->where(function ($qB) use ($query) {
                $qB->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('address', 'like', "%{$query}%");
            });
        }

        if ($filter === 'debt') {
            $customersQuery->whereHas('loans', function ($l) {
                $l->where('type', 'given')->where('status', 'pending');
            });
        } elseif ($filter === 'clear') {
            $customersQuery->whereDoesntHave('loans', function ($l) {
                $l->where('type', 'given')->where('status', 'pending');
            });
        }

        $customers = $customersQuery
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $totalCustomers = Customer::count();
        $customersWithDebt = Customer::whereHas('loans', function ($l) {
            $l->where('type', 'given')->where('status', 'pending');
        })->count();

        $pendingLoansSum = Loan::where('type', 'given')
            ->where('status', 'pending')
            ->withSum('payments', 'amount')
            ->get();
        $totalOutstandingDebt = (float) $pendingLoansSum->sum(fn ($l) => max(0, round((float) $l->amount - (float) ($l->payments_sum_amount ?? 0), 2)));

        return view('customers.index', [
            'customers'            => $customers,
            'query'                => $query,
            'filter'               => $filter,
            'totalCustomers'       => $totalCustomers,
            'customersWithDebt'    => $customersWithDebt,
            'totalOutstandingDebt' => $totalOutstandingDebt,
        ]);
    }

    public function show(Request $request, Customer $customer)
    {
        // 1. Pending given loans (with sale & payments eager loaded)
        $pendingLoans = $customer->loans()
            ->where('type', 'given')
            ->where('status', 'pending')
            ->with(['sale', 'payments.user'])
            ->withSum('payments', 'amount')
            ->orderBy('loan_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 2. Settled/paid loans
        $settledLoans = $customer->loans()
            ->where('type', 'given')
            ->where('status', 'paid')
            ->with(['sale', 'payments.user'])
            ->withSum('payments', 'amount')
            ->orderBy('loan_date', 'desc')
            ->take(10)
            ->get();

        // 3. Payment history across all loans for this customer
        $loanIds = $customer->loans()->where('type', 'given')->pluck('id');
        $paymentHistory = LoanPayment::whereIn('loan_id', $loanIds)
            ->with(['loan.sale', 'user'])
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'payments_page');

        // 4. Summaries
        $totalDebt = (float) $pendingLoans->sum(fn ($l) => max(0, round((float) $l->amount - (float) ($l->payments_sum_amount ?? 0), 2)));
        $totalPaidOnLoans = (float) LoanPayment::whereIn('loan_id', $loanIds)->sum('amount');
        $totalSalesAmount = (float) $customer->sales()->where('is_gift', false)->sum('total_amount');
        $totalSalesCount  = (int) $customer->sales()->count();

        // 5. Payment channels
        $paymentChannels = PaymentChannel::where('is_active', true)->get();
        if ($paymentChannels->isEmpty()) {
            $paymentChannels = collect([
                (object) ['slug' => 'cash', 'name' => 'Cash'],
                (object) ['slug' => 'bank', 'name' => 'Bank Transfer'],
                (object) ['slug' => 'momo', 'name' => 'Mobile Money (MoMo)'],
            ]);
        }

        return view('customers.show', compact(
            'customer',
            'pendingLoans',
            'settledLoans',
            'paymentHistory',
            'totalDebt',
            'totalPaidOnLoans',
            'totalSalesAmount',
            'totalSalesCount',
            'paymentChannels'
        ));
    }

    public function payLoans(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'method'       => ['required', 'string'],
            'notes'        => ['nullable', 'string', 'max:500'],
            'loan_id'      => ['nullable', 'integer', 'exists:loans,id'],
        ]);

        try {
            $amountToPay  = (float) $validated['amount'];
            $paymentDate  = $validated['payment_date'];
            $method       = strtolower(trim($validated['method']));
            $notes        = $validated['notes'] ?? null;
            $targetLoanId = $validated['loan_id'] ?? null;
            $userId       = Auth::id();

            DB::transaction(function () use ($customer, $amountToPay, $paymentDate, $method, $notes, $targetLoanId, $userId) {
                $query = $customer->loans()
                    ->where('type', 'given')
                    ->where('status', 'pending')
                    ->withSum('payments', 'amount')
                    ->orderBy('loan_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate();

                $pendingLoans = $query->get();

                if ($pendingLoans->isEmpty()) {
                    throw new \RuntimeException('This customer has no active pending loans to pay.');
                }

                // If a specific loan was targeted, pay it first, then continue FIFO
                if ($targetLoanId) {
                    $pendingLoans = $pendingLoans->sortBy(fn ($l) => $l->id == $targetLoanId ? 0 : 1);
                }

                $totalDebt = (float) $pendingLoans->sum(fn ($l) => max(0, round((float) $l->amount - (float) ($l->payments_sum_amount ?? 0), 2)));

                if ($amountToPay > ($totalDebt + 0.01)) {
                    throw new \RuntimeException('Payment amount (' . number_format($amountToPay, 2) . ') exceeds total customer debt (' . number_format($totalDebt, 2) . ').');
                }

                $remainingToAllocate = $amountToPay;

                foreach ($pendingLoans as $loan) {
                    $paidSoFar = (float) ($loan->payments_sum_amount ?? $loan->payments()->sum('amount'));
                    $loanRemaining = max(0.0, round((float) $loan->amount - $paidSoFar, 2));

                    if ($loanRemaining <= 0.009) {
                        continue;
                    }

                    $payThis = min($remainingToAllocate, $loanRemaining);

                    // 1. Create LoanPayment
                    $paymentNote = $notes
                        ? "{$notes} • Installment for Loan #{$loan->id}" . ($loan->sale_id ? " (Sale #{$loan->sale_id})" : "")
                        : "Customer installment for Loan #{$loan->id}" . ($loan->sale_id ? " (Sale #{$loan->sale_id})" : "");

                    $loan->payments()->create([
                        'user_id'      => $userId,
                        'amount'       => $payThis,
                        'payment_date' => $paymentDate,
                        'method'       => $method,
                        'notes'        => $paymentNote,
                    ]);

                    // 2. Financial Transaction (Credit = cash inflow)
                    $txnNote = "Customer loan payment for Loan #{$loan->id}" . ($loan->sale_id ? " (Sale #{$loan->sale_id})" : "");
                    if ($notes) {
                        $txnNote .= " • {$notes}";
                    }

                    $transaction = Transaction::create([
                        'type'             => 'credit',
                        'amount'           => $payThis,
                        'transaction_date' => $paymentDate,
                        'method'           => $method,
                        'user_id'          => $userId,
                        'customer_id'      => $customer->id,
                        'loan_id'          => $loan->id,
                        'sale_id'          => $loan->sale_id,
                        'notes'            => $txnNote,
                    ]);

                    // 3. DebitCredit entry
                    DebitCredit::create([
                        'type'           => 'credit',
                        'amount'         => $payThis,
                        'description'    => "Customer loan payment - Loan #{$loan->id}" . ($loan->sale_id ? " (Sale #{$loan->sale_id})" : ""),
                        'date'           => $paymentDate,
                        'user_id'        => $userId,
                        'customer_id'    => $customer->id,
                        'transaction_id' => $transaction->id,
                    ]);

                    // 4. Update loan status if settled
                    $newTotalPaid = $paidSoFar + $payThis;
                    if ($newTotalPaid >= ((float) $loan->amount - 0.01)) {
                        $loan->update(['status' => 'paid']);
                    }

                    $remainingToAllocate -= $payThis;

                    if ($remainingToAllocate <= 0.009) {
                        break;
                    }
                }
            });

            return redirect()->route('customers.show', $customer)
                ->with('success', 'Payment of ' . number_format($amountToPay, 2) . ' recorded successfully.');
        } catch (\Throwable $e) {
            Log::error('Customer payLoans failed', ['customer_id' => $customer->id, 'error' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Payment failed: ' . $e->getMessage()]);
        }
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $customer = Customer::create($data);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer created successfully.');
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $customer->update($data);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }
}
