<?php

namespace App\Http\Controllers;

use App\Models\{ Loan, LoanPayment, Customer, Supplier };
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoanController extends Controller
{
    /* =========================================================
     | Helpers
     * =======================================================*/

    /**
     * Apply common eager loads, sums, filters, and sorting.
     * Supports: type, status, customer_id, supplier_id, from, to, q, party, period, overdue
     */
    protected function baseQuery(Request $request)
    {
        $q = Loan::query()
            // fast paid total for each row (payments_sum_amount)
            ->withSum('payments', 'amount')
            // light party eager-loads for table rendering/exports
            ->with(['customer:id,name', 'supplier:id,name']);

        // ---- Quick period (only if from/to not provided)
        $period = trim((string) $request->input('period', ''));
        if (!$request->filled('from') && !$request->filled('to') && $period) {
            [$pf, $pt] = $this->periodBounds($period);
            if ($pf && $pt) {
                $q->whereBetween('loan_date', [$pf, $pt]);
            }
        }

        // ---- Explicit date bounds (override period)
        if ($request->filled('from') || $request->filled('to')) {
            $from = $request->date('from') ?: Carbon::minValue();
            $to   = $request->date('to')   ?: Carbon::maxValue();
            $q->whereBetween('loan_date', [$from, $to]);
        }

        // ---- Basic filters
        $q->when($request->filled('type'), fn($x) => $x->where('type', $request->string('type')));
        $q->when($request->filled('status'), fn($x) => $x->where('status', $request->string('status')));
        $q->when($request->filled('customer_id'), fn($x) => $x->where('customer_id', $request->integer('customer_id')));
        $q->when($request->filled('supplier_id'), fn($x) => $x->where('supplier_id', $request->integer('supplier_id')));

        // ---- Overdue toggle
        if ($request->boolean('overdue')) {
            $q->where('status', 'pending')
              ->whereNotNull('due_date')
              ->where('due_date', '<', now()->startOfDay());
        }

        // ---- Text search (q) across id, notes, party names
        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $op   = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $q->where(function ($w) use ($term, $op) {
                $w->where('id', (int) $term ?: -1)
                  ->orWhere('notes', $op, "%{$term}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', $op, "%{$term}%"))
                  ->orWhereHas('supplier', fn($s) => $s->where('name', $op, "%{$term}%"));
            });
        }

        // ---- Party-only search (separate from q)
        if ($request->filled('party')) {
            $term = trim((string) $request->input('party'));
            $op   = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $q->where(function ($w) use ($term, $op) {
                $w->whereHas('customer', fn($c) => $c->where('name', $op, "%{$term}%"))
                  ->orWhereHas('supplier', fn($s) => $s->where('name', $op, "%{$term}%"));
            });
        }

        // ---- Sorting (defaults to latest by loan_date)
        $sort = strtolower((string) $request->input('sort', ''));
        $dir  = strtolower((string) $request->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        return match ($sort) {
            'amount'    => $q->orderBy('amount', $dir)->orderBy('id', 'desc'),
            'loan_date' => $q->orderBy('loan_date', $dir)->orderBy('id', 'desc'),
            'due_date'  => $q->orderBy('due_date', $dir)->orderBy('id', 'desc'),
            default     => $q->orderBy('loan_date', 'desc')->orderBy('id', 'desc'),
        };
    }

    /**
     * Map quick period chips into Carbon ranges.
     */
    protected function periodBounds(?string $period): array
    {
        $period = strtolower((string) $period);
        $today  = now()->startOfDay();

        return match ($period) {
            'today', 'day'   => [$today, $today->copy()->endOfDay()],
            'week'           => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'month'          => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'quarter'        => [$today->copy()->firstOfQuarter(), $today->copy()->lastOfQuarter()],
            'year'           => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            default          => [null, null],
        };
    }

    /**
     * Compute stats from a filtered query (not paginated).
     */
    protected function statsFromQuery($query): array
    {
        $clone = (clone $query);
        return [
            'total_loans'   => (float) $clone->sum('amount'),
            'paid_loans'    => (float) (clone $query)->where('status', 'paid')->sum('amount'),
            'pending_loans' => (float) (clone $query)->where('status', 'pending')->sum('amount'),
            'count_given'   => (clone $query)->where('type', 'given')->count(),
            'count_taken'   => (clone $query)->where('type', 'taken')->count(),
        ];
    }

    /* =========================================================
     | Resource Actions
     * =======================================================*/

    /**
     * Display loans with filters & page stats. JSON supported.
     */
    public function index(Request $request)
    {
        $perPage = max((int) $request->input('per_page', 10), 1);
        $query   = $this->baseQuery($request);

        $loans = $query->paginate($perPage)->withQueryString();

        // Stats for the currently filtered dataset (not just the page)
        $stats = $this->statsFromQuery($this->baseQuery($request)->select('id','amount','status','type'));

        Log::info('📋 Loans list viewed', ['filters' => $request->all(), 'total_filtered' => $loans->total()]);

        if ($request->wantsJson()) {
            return response()->json([
                'data'       => $loans->items(),
                'pagination' => [
                    'current_page' => $loans->currentPage(),
                    'last_page'    => $loans->lastPage(),
                    'per_page'     => $loans->perPage(),
                    'total'        => $loans->total(),
                ],
                'stats' => $stats,
            ]);
        }

        return view('loans.index', compact('loans', 'stats'));
    }

    /**
     * Range preset view (reuses index blade).
     * GET /loans/range/{range}
     */
    public function range(Request $request, string $range)
    {
        [$from, $to] = match (strtolower($range)) {
            'day'     => [now()->startOfDay(), now()->endOfDay()],
            'week'    => [now()->startOfWeek(), now()->endOfWeek()],
            'month'   => [now()->startOfMonth(), now()->endOfMonth()],
            'quarter' => [now()->firstOfQuarter(), now()->lastOfQuarter()],
            'year'    => [now()->startOfYear(), now()->endOfYear()],
            default   => [now()->startOfMonth(), now()->endOfMonth()],
        };

        $requestWithRange = $request->merge([
            'from' => $from->toDateString(),
            'to'   => $to->toDateString(),
        ]);

        $perPage = max((int) $request->input('per_page', 10), 1);
        $query   = $this->baseQuery($requestWithRange);

        $loans = $query->paginate($perPage)->withQueryString();
        $stats = $this->statsFromQuery($query->select('id','amount','status','type'));

        if ($request->wantsJson()) {
            return response()->json([
                'data'  => $loans->items(),
                'range' => ['key' => $range, 'from' => $from->toDateString(), 'to' => $to->toDateString()],
                'stats' => $stats,
                'pagination' => [
                    'current_page' => $loans->currentPage(),
                    'last_page'    => $loans->lastPage(),
                    'per_page'     => $loans->perPage(),
                    'total'        => $loans->total(),
                ],
            ]);
        }

        return view('loans.index', [
            'loans'       => $loans,
            'stats'       => $stats,
            'activeRange' => $range,
            'from'        => $from,
            'to'          => $to,
        ]);
    }

    /**
     * Party-focused listing (customer/supplier).
     */
    public function party(Request $request, string $party, int $id)
    {
        $perPage = max((int) $request->input('per_page', 10), 1);

        $request = $party === 'customer'
            ? $request->merge(['customer_id' => $id])
            : $request->merge(['supplier_id' => $id]);

        $query = $this->baseQuery($request);
        $loans = $query->paginate($perPage)->withQueryString();
        $stats = $this->statsFromQuery($query->select('id','amount','status','type'));

        if ($request->wantsJson()) {
            return response()->json([
                'party'      => $party,
                'party_id'   => $id,
                'data'       => $loans->items(),
                'stats'      => $stats,
                'pagination' => [
                    'current_page' => $loans->currentPage(),
                    'last_page'    => $loans->lastPage(),
                    'per_page'     => $loans->perPage(),
                    'total'        => $loans->total(),
                ],
            ]);
        }

        return view('loans.index', compact('loans', 'stats'));
    }

    /**
     * Insights for dashboard cards/charts (JSON).
     * Optional filters: type, status, from, to, period, overdue, q, party
     */
    public function insights(Request $request)
    {
        $q = $this->baseQuery($request);

        $today    = now()->startOfDay();
        $in7days  = now()->addDays(7)->endOfDay();

        $totals = [
            'amount_total'   => (float) (clone $q)->sum('amount'),
            'amount_paid'    => (float) (clone $q)->where('status','paid')->sum('amount'),
            'amount_pending' => (float) (clone $q)->where('status','pending')->sum('amount'),
            'count'          => (clone $q)->count(),
            'overdue'        => (clone $q)->where('status','pending')->whereNotNull('due_date')->where('due_date', '<', $today)->count(),
            'due_soon'       => (clone $q)->where('status','pending')->whereBetween('due_date', [$today, $in7days])->count(),
        ];

        // Last 6 months buckets (DB-agnostic)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $series = (clone $q)
                ->selectRaw("DATE_TRUNC('month', loan_date) as period, SUM(amount) as total")
                ->where('loan_date', '>=', now()->subMonths(5)->startOfMonth())
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(fn($r) => ['period' => Carbon::parse($r->period)->format('Y-m-01'), 'total' => (float)$r->total]);
        } else {
            $series = (clone $q)
                ->selectRaw("DATE_FORMAT(loan_date, '%Y-%m-01') as period, SUM(amount) as total")
                ->where('loan_date', '>=', now()->subMonths(5)->startOfMonth())
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(fn($r) => ['period' => (string)$r->period, 'total' => (float)$r->total]);
        }

        return response()->json([
            'totals' => $totals,
            'series' => $series,
        ]);
    }

    /**
     * Calendar feed of due dates (JSON events).
     * ?from=YYYY-MM-DD&to=YYYY-MM-DD (defaults: next 90 days)
     */
    public function calendarFeed(Request $request)
    {
        $from = $request->date('from') ?: now()->startOfDay();
        $to   = $request->date('to')   ?: now()->addDays(90)->endOfDay();

        $loans = Loan::with(['customer:id,name', 'supplier:id,name'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$from, $to])
            ->orderBy('due_date')
            ->get();

        $events = $loans->map(function ($loan) {
            $party = $loan->type === 'given'
                ? ($loan->customer->name ?? 'Customer')
                : ($loan->supplier->name ?? 'Supplier');

            return [
                'id'     => $loan->id,
                'title'  => strtoupper($loan->type) . ' · ' . $party . ' · ' . number_format($loan->amount, 2),
                'start'  => optional($loan->due_date)->toDateString(),
                'end'    => optional($loan->due_date)->toDateString(),
                'url'    => route('loans.show', $loan),
                'status' => $loan->status,
            ];
        });

        return response()->json([
            'from'   => $from->toDateString(),
            'to'     => $to->toDateString(),
            'events' => $events,
        ]);
    }

    /**
     * Mark a loan as fully paid (no auto-payment record).
     */
    public function markPaid(Request $request, Loan $loan)
    {
        if ($loan->status === 'paid') {
            return back()->with('success', 'Loan is already marked as paid.');
        }

        $loan->update(['status' => 'paid']);
        Log::info('✅ Loan manually marked as paid', ['loan_id' => $loan->id, 'by' => auth()->id()]);

        return back()->with('success', 'Loan marked as paid.');
    }

    /**
     * Recalculate paid/remaining and update status if fully paid.
     */
    public function recalculate(Request $request, Loan $loan)
    {
        $totalPaid = (float) $loan->payments()->sum('amount');
        $remaining = round(($loan->amount ?? 0) - $totalPaid, 2);

        if ($remaining <= 0.009 && $loan->status !== 'paid') {
            $loan->update(['status' => 'paid']);
            Log::info('🔄 Loan auto-closed during recalc', ['loan_id' => $loan->id]);
        }

        return back()->with('success', 'Loan recalculated.');
    }

    /**
     * Export filtered loans to CSV.
     * Respects all current filters/query string.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $query = $this->baseQuery($request)
            ->select(['id','type','customer_id','supplier_id','amount','loan_date','due_date','status'])
            ->withSum('payments','amount');

        $filename = 'loans_' . now()->format('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        return response()->stream(function () use ($query) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['ID','Type','Party','Amount','Paid','Remaining','Status','Loan Date','Due Date']);

            $query->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $loan) {
                    $paid = (float) ($loan->payments_sum_amount ?? 0);
                    $rem  = round(max(($loan->amount ?? 0) - $paid, 0), 2);

                    $party = $loan->type === 'given'
                        ? optional($loan->customer)->name
                        : optional($loan->supplier)->name;

                    fputcsv($out, [
                        $loan->id,
                        $loan->type,
                        $party ?: '—',
                        number_format((float)$loan->amount, 2, '.', ''),
                        number_format($paid, 2, '.', ''),
                        number_format($rem, 2, '.', ''),
                        $loan->status,
                        optional($loan->loan_date)->toDateString(),
                        optional($loan->due_date)->toDateString(),
                    ]);
                }
            });

            fclose($out);
        }, 200, $headers);
    }

    /* =========================================================
     | Standard CRUD (upgraded validation)
     * =======================================================*/

    public function create()
    {
        $customers = Customer::orderBy('name')->get(['id','name','email']);
        $suppliers = Supplier::orderBy('name')->get(['id','name','email']);

        Log::info('📝 Opening loan creation form');

        return view('loans.create', compact('customers', 'suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'        => ['required', Rule::in(['given','taken'])],
            'amount'      => ['required','numeric','min:0.01','max:999999999999.99'],
            'loan_date'   => ['required','date'],
            'due_date'    => ['nullable','date','after_or_equal:loan_date'],
            'status'      => ['required', Rule::in(['pending','paid'])],
            'customer_id' => ['nullable','integer','exists:customers,id'],
            'supplier_id' => ['nullable','integer','exists:suppliers,id'],
            'notes'       => ['nullable','string','max:500'],
        ]);

        if ($data['type'] === 'given' && empty($data['customer_id'])) {
            return back()->withErrors(['customer_id' => 'Customer is required for a "given" loan.'])->withInput();
        }
        if ($data['type'] === 'taken' && empty($data['supplier_id'])) {
            return back()->withErrors(['supplier_id' => 'Supplier is required for a "taken" loan.'])->withInput();
        }

        try {
            DB::beginTransaction();

            $loan = Loan::create($data);

            Log::info('✅ Loan created manually', [
                'loan_id' => $loan->id,
                'type'    => $loan->type,
                'amount'  => $loan->amount,
                'by_user' => auth()->id(),
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Created', 'loan' => $loan->fresh()], 201);
            }

            return redirect()->route('loans.index')->with('success', 'Loan recorded successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Loan creation failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to create loan: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Loan $loan)
    {
        $loan->load(['customer','supplier','payments.user']);

        $totalPaid = $loan->payments->sum('amount');
        $remaining = max(($loan->amount ?? 0) - $totalPaid, 0);

        Log::info('📖 Viewing loan details', [
            'loan_id'   => $loan->id,
            'paid'      => $totalPaid,
            'remaining' => $remaining,
        ]);

        return view('loans.show', compact('loan', 'totalPaid', 'remaining'));
    }

    public function edit(Loan $loan)
    {
        $customers = Customer::orderBy('name')->get(['id','name']);
        $suppliers = Supplier::orderBy('name')->get(['id','name']);

        Log::info('✏️ Editing loan', ['loan_id' => $loan->id]);

        return view('loans.edit', compact('loan', 'customers', 'suppliers'));
    }

    public function update(Request $request, Loan $loan)
    {
        $data = $request->validate([
            'type'        => ['required', Rule::in(['given','taken'])],
            'amount'      => ['required','numeric','min:0.01','max:999999999999.99'],
            'loan_date'   => ['required','date'],
            'due_date'    => ['nullable','date','after_or_equal:loan_date'],
            'status'      => ['required', Rule::in(['pending','paid'])],
            'customer_id' => ['nullable','integer','exists:customers,id'],
            'supplier_id' => ['nullable','integer','exists:suppliers,id'],
            'notes'       => ['nullable','string','max:500'],
        ]);

        if ($data['type'] === 'given' && empty($data['customer_id'])) {
            return back()->withErrors(['customer_id' => 'Customer is required for a "given" loan.'])->withInput();
        }
        if ($data['type'] === 'taken' && empty($data['supplier_id'])) {
            return back()->withErrors(['supplier_id' => 'Supplier is required for a "taken" loan.'])->withInput();
        }

        try {
            DB::beginTransaction();

            $loan->update($data);

            // Auto close if fully paid
            $totalPaid = (float) $loan->payments()->sum('amount');
            if ($totalPaid >= ($loan->amount ?? 0) && $loan->status !== 'paid') {
                $loan->update(['status' => 'paid']);
                Log::info('🔁 Loan auto-marked as paid on update', ['loan_id' => $loan->id]);
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Updated', 'loan' => $loan->fresh()], 200);
            }

            return redirect()->route('loans.show', $loan)->with('success', 'Loan updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Loan update failed', ['loan_id' => $loan->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to update loan: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(Request $request, Loan $loan)
    {
        try {
            DB::transaction(function () use ($loan) {
                $loan->payments()->delete();
                $loan->delete();
            });

            Log::info('🗑️ Loan deleted', ['loan_id' => $loan->id]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Deleted'], 200);
            }

            return redirect()->route('loans.index')->with('success', 'Loan deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('❌ Loan delete failed', ['loan_id' => $loan->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to delete loan: ' . $e->getMessage()]);
        }
    }

    /**
     * Export filtered loans to PDF.
     * ✅ Respects current filters (query string) so it downloads only what you searched.
     */
    public function exportPdf(Request $request)
    {
        // Use same filtered dataset as the index
        $q = $this->baseQuery($request)
            ->select(['id','type','customer_id','supplier_id','amount','loan_date','due_date','status'])
            ->withSum('payments','amount');

        $loans = $q->get();

        // Summary from the SAME filtered query
        $summary = [
            'totalGiven'   => (float) (clone $q)->where('type', 'given')->sum('amount'),
            'totalTaken'   => (float) (clone $q)->where('type', 'taken')->sum('amount'),
            'totalPaid'    => (float) (clone $q)->where('status', 'paid')->sum('amount'),
            'totalPending' => (float) (clone $q)->where('status', 'pending')->sum('amount'),
        ];

        Log::info('📤 Exporting filtered loan summary PDF', [
            'filters' => $request->all(),
            'count'   => $loans->count(),
        ]);

        $pdf = Pdf::loadView('loans.pdf.summary', [
            'loans'   => $loans,
            'summary' => $summary,
            'filters' => $request->all(),
        ])->setPaper('a4');

        return $pdf->download('loan_summary_' . now()->format('Y_m_d_His') . '.pdf');
    }
}
