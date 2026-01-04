<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Loan;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AuditReportController extends Controller
{
    /**
     * Display Audit Logs
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->latest()->paginate(20);

        return view('reports.audit', compact('logs'));
    }

    /**
     * Financial Overview (Pro Features)
     */
    public function financials(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Sales Stats
        $sales = Sale::whereBetween('sale_date', [$startDate, $endDate])->get();
        $totalSales = $sales->sum('total_amount');
        $totalReceived = $sales->sum('amount_paid');
        $totalProfit = $sales->sum(function($sale) {
            return $sale->items->sum('profit') ?? 0;
        });

        // Purchase Stats
        $purchases = Purchase::whereBetween('purchase_date', [$startDate, $endDate])->get();
        $totalPurchases = $purchases->sum('total_amount');

        // Loan Stats
        $loansGiven = Loan::whereBetween('loan_date', [$startDate, $endDate])->where('type', 'given')->sum('amount');
        $loansTaken = Loan::whereBetween('loan_date', [$startDate, $endDate])->where('type', 'taken')->sum('amount');

        return view('reports.financials', compact(
            'startDate', 'endDate',
            'totalSales', 'totalReceived', 'totalProfit',
            'totalPurchases',
            'loansGiven', 'loansTaken'
        ));
    }

    /**
     * Mistake Detection / Reconciliation
     */
    public function reconciliation()
    {
        $issues = [];

        // 1. Detect Sales with Payment Mismatches
        // Logic: If status is 'paid' but amount_paid < total_amount
        $badPaidSales = Sale::where('status', 'paid')
            ->whereRaw('amount_paid < total_amount - 1') // Allow small tolerance
            ->get();
        
        foreach ($badPaidSales as $sale) {
            $issues[] = [
                'type' => 'Sale Error',
                'item_id' => $sale->id,
                'message' => "Sale #{$sale->id} is marked 'Paid' but amount paid ({$sale->amount_paid}) is less than total ({$sale->total_amount}).",
                'severity' => 'high',
                'link' => route('sales.show', $sale->id),
            ];
        }

        // 2. Detect Overpaid Sales
        $overpaidSales = Sale::whereRaw('amount_paid > total_amount + 1')->get();
        foreach ($overpaidSales as $sale) {
            $issues[] = [
                'type' => 'Sale Warning',
                'item_id' => $sale->id,
                'message' => "Sale #{$sale->id} has been overpaid! Total: {$sale->total_amount}, Paid: {$sale->amount_paid}.",
                'severity' => 'medium',
                'link' => route('sales.show', $sale->id),
            ];
        }

        // 3. Loans that are 'paid' but have remaining balance
        // We need to calculate remaining carefully or rely on model attribute if loaded
        // Let's use raw query for speed
        $badLoans = Loan::where('status', 'paid')->get()->filter(function($loan) {
             return $loan->remaining > 1; 
        });

        foreach ($badLoans as $loan) {
            $issues[] = [
                'type' => 'Loan Error',
                'item_id' => $loan->id,
                'message' => "Loan #{$loan->id} ({$loan->type}) is marked 'Paid' but still has {$loan->remaining} remaining.",
                'severity' => 'high',
                'link' => '#', // route('loans.show', $loan->id)
            ];
        }
        
        return view('reports.reconciliation', compact('issues'));
    }
}
