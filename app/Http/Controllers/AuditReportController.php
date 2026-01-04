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

        // Revenue by Payment Channel (Group by 'payment_channel' on Sale)
        // Note: For more granularity (split payments), we could query SalePayment.
        // But for now, using Sale's primary channel is faster and consistent with current usage.
        $revenueByChannel = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->select('payment_channel', DB::raw('sum(amount_paid) as total'))
            ->groupBy('payment_channel')
            ->pluck('total', 'payment_channel');

        // Full Lists (No Pagination as requested for report view)
        $allSales = Sale::with(['customer', 'user'])
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->latest('sale_date')
            ->get();
            
        $allPurchases = Purchase::with(['supplier', 'user'])
            ->whereBetween('purchase_date', [$startDate, $endDate])
            ->latest('purchase_date')
            ->get();

        return view('reports.financials', compact(
            'startDate', 'endDate',
            'totalSales', 'totalReceived', 'totalProfit',
            'totalPurchases',
            'loansGiven', 'loansTaken',
            'revenueByChannel', 'allSales', 'allPurchases'
        ));
    }

    /**
     * Mistake Detection / Reconciliation
     */
    public function reconciliation()
    {
        // Increase time limit for this specific report (allow 2 minutes)
        set_time_limit(120);

        $issues = [];
        $limit  = 100; // Cap to prevent memory exhaustion

        // 1. Detect Sales with Payment Mismatches
        // Logic: Sales that are 'completed' but paid amount < total
        $badPaidSales = Sale::where('status', 'completed')
            ->whereRaw('amount_paid < total_amount - 1')
            ->latest()
            ->limit($limit)
            ->get();
        
        foreach ($badPaidSales as $sale) {
            $issues[] = [
                'type' => 'Sale Error',
                'item_id' => $sale->id,
                'message' => "Sale #{$sale->id} is 'Completed' but Paid ({$sale->amount_paid}) < Total ({$sale->total_amount}).",
                'severity' => 'high',
                'link' => route('sales.show', $sale->id),
            ];
        }

        // 2. Detect Overpaid Sales
        // Logic: Sales where paid > total
        $overpaidSales = Sale::whereRaw('amount_paid > total_amount + 1')
            ->latest()
            ->limit($limit)
            ->get();

        foreach ($overpaidSales as $sale) {
            $issues[] = [
                'type' => 'Sale Warning',
                'item_id' => $sale->id,
                'message' => "Sale #{$sale->id} Overpaid! Total: {$sale->total_amount}, Paid: {$sale->amount_paid}.",
                'severity' => 'medium',
                'link' => route('sales.show', $sale->id),
            ];
        }

        // 3. Loans marked 'paid' but have remaining balance
        // CRITICAL OPTIMIZATION: Use SQL subquery instead of iterating all loans
        $badLoans = Loan::where('status', 'paid')
            ->whereRaw('(amount - (select COALESCE(sum(amount), 0) from loan_payments where loan_payments.loan_id = loans.id)) > 1')
            ->limit($limit) // Limit results
            ->get();

        foreach ($badLoans as $loan) {
            $paid = $loan->payments()->sum('amount');
            $rem  = $loan->amount - $paid;

            $issues[] = [
                'type' => 'Loan Error',
                'item_id' => $loan->id,
                'message' => "Loan #{$loan->id} ({$loan->type}) is marked 'Paid' but has balance {$rem}.",
                'severity' => 'high',
                'link' => '#', 
            ];
        }

        // 4. Loans that are 'pending' but fully paid (Bonus check)
        $forgottenLoans = Loan::where('status', 'pending')
             ->whereRaw('(amount - (select COALESCE(sum(amount), 0) from loan_payments where loan_payments.loan_id = loans.id)) < 1')
             ->limit($limit)
             ->get();

        foreach ($forgottenLoans as $loan) {
             $issues[] = [
                'type' => 'Loan Status',
                'item_id' => $loan->id,
                'message' => "Loan #{$loan->id} is fully paid but status is 'Pending'.",
                'severity' => 'medium',
                'link' => '#',
            ];
        }
        
        return view('reports.reconciliation', compact('issues'));
    }
}
