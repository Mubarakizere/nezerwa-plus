<?php

namespace App\Http\Controllers;

use App\Models\{
    Loan,
    LoanPayment,
    Customer,
    Supplier
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class LoanController extends Controller
{
    /**
     * Display all loans with summaries.
     */
    public function index()
    {
        $loans = Loan::with(['customer', 'supplier', 'payments'])->latest()->paginate(10);


        $stats = [
            'total_loans'   => Loan::sum('amount'),
            'paid_loans'    => Loan::where('status', 'paid')->sum('amount'),
            'pending_loans' => Loan::where('status', 'pending')->sum('amount'),
            'count_given'   => Loan::where('type', 'given')->count(),
            'count_taken'   => Loan::where('type', 'taken')->count(),
        ];

        Log::info('📋 Loans list viewed', ['total_loans' => $stats['total_loans']]);

        return view('loans.index', compact('loans', 'stats'));
    }

    /**
     * Show create loan form.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        Log::info('📝 Opening loan creation form');

        return view('loans.create', compact('customers', 'suppliers'));
    }

    /**
     * Store a new loan manually.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type'        => 'required|in:given,taken',
            'amount'      => 'required|numeric|min:1',
            'loan_date'   => 'required|date',
            'due_date'    => 'nullable|date|after_or_equal:loan_date',
            'status'      => 'required|in:pending,paid',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'notes'       => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $loan = Loan::create($request->all());

            Log::info('✅ Loan created manually', [
                'loan_id' => $loan->id,
                'type'    => $loan->type,
                'amount'  => $loan->amount,
                'by_user' => auth()->id(),
            ]);

            DB::commit();
            return redirect()->route('loans.index')->with('success', 'Loan recorded successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Loan creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Failed to create loan: ' . $e->getMessage()]);
        }
    }

    /**
     * Show detailed loan info with payments.
     */
    public function show(Loan $loan)
    {
        $loan->load(['customer', 'supplier', 'payments.user']);

        $totalPaid = $loan->payments->sum('amount');
        $remaining = max($loan->amount - $totalPaid, 0);

        Log::info('📖 Viewing loan details', [
            'loan_id'   => $loan->id,
            'paid'      => $totalPaid,
            'remaining' => $remaining,
        ]);

        return view('loans.show', compact('loan', 'totalPaid', 'remaining'));
    }

    /**
     * Edit existing loan.
     */
    public function edit(Loan $loan)
    {
        $customers = Customer::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        Log::info('✏️ Editing loan', ['loan_id' => $loan->id]);

        return view('loans.edit', compact('loan', 'customers', 'suppliers'));
    }

    /**
     * Update existing loan.
     */
    public function update(Request $request, Loan $loan)
    {
        $request->validate([
            'type'        => 'required|in:given,taken',
            'amount'      => 'required|numeric|min:1',
            'loan_date'   => 'required|date',
            'due_date'    => 'nullable|date|after_or_equal:loan_date',
            'status'      => 'required|in:pending,paid',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'notes'       => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $loan->update($request->all());
            if (method_exists($loan, 'checkIfFullyPaid')) {
                $loan->checkIfFullyPaid();
            }

            DB::commit();
            Log::info('🔁 Loan updated successfully', [
                'loan_id' => $loan->id,
                'status'  => $loan->status,
            ]);

            return redirect()->route('loans.show', $loan)->with('success', 'Loan updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Loan update failed', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to update loan: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete loan (and related payments).
     */
    public function destroy(Loan $loan)
    {
        try {
            DB::transaction(function () use ($loan) {
                $loan->payments()->delete();
                $loan->delete();
            });

            Log::info('🗑️ Loan deleted', ['loan_id' => $loan->id]);

            return redirect()->route('loans.index')->with('success', 'Loan deleted successfully.');

        } catch (\Throwable $e) {
            Log::error('❌ Loan delete failed', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to delete loan: ' . $e->getMessage()]);
        }
    }

    /**
     * Export loans summary to PDF.
     */
    public function exportPdf()
    {
        $loans = Loan::with(['customer', 'supplier'])->get();

        $summary = [
            'totalGiven'   => Loan::where('type', 'given')->sum('amount'),
            'totalTaken'   => Loan::where('type', 'taken')->sum('amount'),
            'totalPaid'    => Loan::where('status', 'paid')->sum('amount'),
            'totalPending' => Loan::where('status', 'pending')->sum('amount'),
        ];

        Log::info('📤 Exporting loan summary PDF', $summary);

        $pdf = Pdf::loadView('loans.pdf.summary', compact('loans', 'summary'))
            ->setPaper('a4');

        return $pdf->download('loan_summary_' . now()->format('Y_m_d') . '.pdf');
    }
}
