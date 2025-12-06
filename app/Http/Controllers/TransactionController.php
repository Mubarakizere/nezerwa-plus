<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;
use Throwable;

class TransactionController extends Controller
{
    /* =========================
     |  LIST / INDEX
     |=========================*/
    public function index(Request $request)
    {
        $rid  = uniqid('tx_index_');
        $isPg = DB::getDriverName() === 'pgsql';
        $like = $isPg ? 'ilike' : 'like';

        try {
            // Base query with relations + text search
            $base = Transaction::query()
                ->with(['user','customer','supplier','sale','purchase'])
                ->when($request->filled('type'),        fn($q) => $q->where('type', $request->type))
                ->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $request->customer_id))
                ->when($request->filled('supplier_id'), fn($q) => $q->where('supplier_id', $request->supplier_id))
                ->when($request->filled('method'),      fn($q) => $q->where('method', $request->method))
                ->when($request->filled('q'), function ($q) use ($request, $like) {
                    $term = trim($request->q);
                    $q->where(function ($xx) use ($term, $like) {
                        $xx->where('method', $like, "%{$term}%")
                           ->orWhere('notes',  $like, "%{$term}%")
                           ->orWhereHas('user',     fn($uq) => $uq->where('name', $like, "%{$term}%"))
                           ->orWhereHas('customer', fn($cq) => $cq->where('name', $like, "%{$term}%"))
                           ->orWhereHas('supplier', fn($sq) => $sq->where('name', $like, "%{$term}%"));
                    });
                });

            // Robust TIMESTAMP bounds (inclusive day)
            $from = $request->date_from ? "{$request->date_from} 00:00:00" : null;
            $to   = $request->date_to   ? "{$request->date_to} 23:59:59"   : null;
            if ($from && $to)      { $base->whereBetween('transaction_date', [$from, $to]); }
            elseif ($from)         { $base->where('transaction_date', '>=', $from); }
            elseif ($to)           { $base->where('transaction_date', '<=', $to); }

            // Page query (keep full model columns)
            $query = (clone $base)->select('transactions.*');
            if ($isPg) {
                $query->addSelect(DB::raw("
                    sum(case when type='credit' then amount else -amount end)
                    over (order by transaction_date, id rows unbounded preceding) as running_balance
                "));
            }

            // Filter-aware totals
            $totalCredits = (clone $base)->where('type', 'credit')->sum('amount');
            $totalDebits  = (clone $base)->where('type', 'debit')->sum('amount');

            // Page data
            $transactions = $query
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString();

            $pageCredits = $transactions->getCollection()->where('type','credit')->sum('amount');
            $pageDebits  = $transactions->getCollection()->where('type','debit')->sum('amount');

            // --- Channel Balances (All Time) ---
            $channels = \App\Models\PaymentChannel::where('is_active', true)->orderBy('name')->get();
            $channelBalances = [];
            foreach($channels as $ch) {
                // We use raw queries for speed on large datasets, but Eloquent is fine for now
                $in  = Transaction::where('method', $ch->slug)->where('type', 'credit')->sum('amount');
                $out = Transaction::where('method', $ch->slug)->where('type', 'debit')->sum('amount');
                $channelBalances[$ch->name] = $in - $out;
            }

            return view('transactions.index', compact(
                'transactions','totalCredits','totalDebits','pageCredits','pageDebits','channelBalances'
            ));
        } catch (Throwable $e) {
            Log::error('Transactions@index failed', ['rid' => $rid, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load transactions. Ref: '.$rid);
        }
    }

    /* =========================
     |  CREATE / STORE
     |=========================*/
    public function create()
    {
        try {
            $customers = Customer::select('id','name')->orderBy('name')->get();
            $suppliers = Supplier::select('id','name')->orderBy('name')->get();
            $channels  = \App\Models\PaymentChannel::where('is_active', true)->orderBy('name')->get();
            return view('transactions.create', compact('customers','suppliers','channels'));
        } catch (Throwable $e) {
            Log::error('Transactions@create failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to open create form.');
        }
    }

    public function store(Request $request)
    {
        $rid = uniqid('tx_store_');
        try {
            $validated = $request->validate([
                'type'             => 'required|in:credit,debit',
                'amount'           => 'required|numeric|min:0.01',
                'transaction_date' => 'required|date',
                'method'           => 'nullable|string|max:255',
                'notes'            => 'nullable|string|max:1000',
                'customer_id'      => 'nullable|exists:customers,id',
                'supplier_id'      => 'nullable|exists:suppliers,id',
            ]);

            $validated['user_id'] = Auth::id();
            Transaction::create($validated);

            return redirect()->route('transactions.index')->with('success', 'Transaction added successfully.');
        } catch (ValidationException $ve) {
            throw $ve;
        } catch (Throwable $e) {
            Log::error('Transactions@store failed', ['rid' => $rid, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to save transaction. Ref: '.$rid);
        }
    }

    /* =========================
     |  SHOW / EDIT / UPDATE
     |=========================*/
    public function show(Transaction $transaction)
    {
        try {
            $transaction->load(['user','customer','supplier','sale','purchase']);
            return view('transactions.show', compact('transaction'));
        } catch (Throwable $e) {
            Log::error('Transactions@show failed', ['id' => $transaction->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load transaction.');
        }
    }

    public function edit(Transaction $transaction)
    {
        try {
            $customers = Customer::select('id','name')->orderBy('name')->get();
            $suppliers = Supplier::select('id','name')->orderBy('name')->get();
            $channels  = \App\Models\PaymentChannel::where('is_active', true)->orderBy('name')->get();
            return view('transactions.edit', compact('transaction','customers','suppliers','channels'));
        } catch (Throwable $e) {
            Log::error('Transactions@edit failed', ['id' => $transaction->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to open edit form.');
        }
    }

    public function update(Request $request, Transaction $transaction)
    {
        $rid = uniqid('tx_update_');
        try {
            $validated = $request->validate([
                'type'             => 'required|in:credit,debit',
                'amount'           => 'required|numeric|min:0.01',
                'transaction_date' => 'required|date',
                'method'           => 'nullable|string|max:255',
                'notes'            => 'nullable|string|max:1000',
                'customer_id'      => 'nullable|exists:customers,id',
                'supplier_id'      => 'nullable|exists:suppliers,id',
            ]);

            $transaction->update($validated);

            return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
        } catch (ValidationException $ve) {
            throw $ve;
    } catch (Throwable $e) {
            Log::error('Transactions@update failed', ['rid' => $rid, 'id' => $transaction->id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to update transaction. Ref: '.$rid);
        }
    }

    /* =========================
     |  DESTROY
     |=========================*/
    public function destroy(Transaction $transaction)
    {
        $rid = uniqid('tx_destroy_');
        try {
            $transaction->delete();
            return redirect()->route('transactions.index')->with('success', 'Transaction deleted successfully.');
        } catch (Throwable $e) {
            Log::error('Transactions@destroy failed', ['rid' => $rid, 'id' => $transaction->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to delete transaction. Ref: '.$rid);
        }
    }

    /**
     * Helper for filters
     */
    protected function applyFilters($query, Request $request)
    {
        $isPg = DB::getDriverName() === 'pgsql';
        $like = $isPg ? 'ilike' : 'like';

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }
        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function($sub) use ($term, $like) {
                $sub->where('notes', $like, "%{$term}%")
                    ->orWhere('method', $like, "%{$term}%")
                    ->orWhereHas('user', fn($u) => $u->where('name', $like, "%{$term}%"))
                    ->orWhereHas('customer', fn($c) => $c->where('name', $like, "%{$term}%"))
                    ->orWhereHas('supplier', fn($s) => $s->where('name', $like, "%{$term}%"));
            });
        }
    }

    /* =========================
     |  EXPORTS (respect filters)
     |=========================*/
    public function exportCsv(Request $request)
    {
        $rid  = uniqid('tx_csv_');
        $isPg = DB::getDriverName() === 'pgsql';
        $like = $isPg ? 'ilike' : 'like';

        try {
            $base = Transaction::query()
                ->with(['user','customer','supplier'])
                ->when($request->filled('type'),        fn($q) => $q->where('type', $request->type))
                ->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $request->customer_id))
                ->when($request->filled('supplier_id'), fn($q) => $q->where('supplier_id', $request->supplier_id))
                ->when($request->filled('method'),      fn($q) => $q->where('method', $request->method))
                ->when($request->filled('q'), function ($q) use ($request, $like) {
                    $term = trim($request->q);
                    $q->where(function ($xx) use ($term, $like) {
                        $xx->where('method', $like, "%{$term}%")
                           ->orWhere('notes',  $like, "%{$term}%")
                           ->orWhereHas('user',     fn($uq) => $uq->where('name', $like, "%{$term}%"))
                           ->orWhereHas('customer', fn($cq) => $cq->where('name', $like, "%{$term}%"))
                           ->orWhereHas('supplier', fn($sq) => $sq->where('name', $like, "%{$term}%"));
                    });
                });

            $from = $request->date_from ? "{$request->date_from} 00:00:00" : null;
            $to   = $request->date_to   ? "{$request->date_to} 23:59:59"   : null;
            if ($from && $to)      { $base->whereBetween('transaction_date', [$from, $to]); }
            elseif ($from)         { $base->where('transaction_date', '>=', $from); }
            elseif ($to)           { $base->where('transaction_date', '<=', $to); }

            $transactions = $base
                ->select('transactions.*')
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            $filename = 'transactions_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $path     = storage_path('app/exports');
            if (!is_dir($path)) mkdir($path, 0755, true);
            $fullpath = $path . DIRECTORY_SEPARATOR . $filename;

            $handle = fopen($fullpath, 'w+');
            fputcsv($handle, ['Date','Type','Amount','Method','User','Customer','Supplier','Notes']);
            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t->transaction_date,
                    ucfirst($t->type),
                    $t->amount,
                    $t->method ?? '-',
                    $t->user->name ?? '-',
                    $t->customer->name ?? '-',
                    $t->supplier->name ?? '-',
                    $t->notes ?? '-',
                ]);
            }
            fclose($handle);

            return response()->download($fullpath)->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            Log::error('Transactions@exportCsv failed', ['rid' => $rid, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to export CSV. Ref: '.$rid);
        }
    }

    public function exportPdf(Request $request)
    {
        $rid  = uniqid('tx_pdf_');
        $isPg = DB::getDriverName() === 'pgsql';
        $like = $isPg ? 'ilike' : 'like';

        try {
            $base = Transaction::query()
                ->with(['user','customer','supplier'])
                ->when($request->filled('type'),        fn($q) => $q->where('type', $request->type))
                ->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $request->customer_id))
                ->when($request->filled('supplier_id'), fn($q) => $q->where('supplier_id', $request->supplier_id))
                ->when($request->filled('method'),      fn($q) => $q->where('method', $request->method))
                ->when($request->filled('q'), function ($q) use ($request, $like) {
                    $term = trim($request->q);
                    $q->where(function ($xx) use ($term, $like) {
                        $xx->where('method', $like, "%{$term}%")
                           ->orWhere('notes',  $like, "%{$term}%")
                           ->orWhereHas('user',     fn($uq) => $uq->where('name', $like, "%{$term}%"))
                           ->orWhereHas('customer', fn($cq) => $cq->where('name', $like, "%{$term}%"))
                           ->orWhereHas('supplier', fn($sq) => $sq->where('name', $like, "%{$term}%"));
                    });
                });

            $from = $request->date_from ? "{$request->date_from} 00:00:00" : null;
            $to   = $request->date_to   ? "{$request->date_to} 23:59:59"   : null;
            if ($from && $to)      { $base->whereBetween('transaction_date', [$from, $to]); }
            elseif ($from)         { $base->where('transaction_date', '>=', $from); }
            elseif ($to)           { $base->where('transaction_date', '<=', $to); }

            $transactions = $base
                ->select('transactions.*')
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            // --- Channel Balances (All Time) ---
            $channels = \App\Models\PaymentChannel::where('is_active', true)->orderBy('name')->get();
            $channelBalances = [];
            foreach($channels as $ch) {
                $in  = Transaction::where('method', $ch->slug)->where('type', 'credit')->sum('amount');
                $out = Transaction::where('method', $ch->slug)->where('type', 'debit')->sum('amount');
                $channelBalances[$ch->name] = $in - $out;
            }

            $pdf  = Pdf::loadView('transactions.export-pdf', compact('transactions', 'channelBalances'));
            $name = 'transactions_' . now()->format('Y-m-d_H-i-s') . '.pdf';
            return $pdf->download($name);
        } catch (Throwable $e) {
            Log::error('Transactions@exportPdf failed', ['rid' => $rid, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to export PDF. Ref: '.$rid);
        }
    }

    /* =========================
     |  WITHDRAWAL (Send to Boss)
     |=========================*/
    public function withdrawal()
    {
        try {
            $channels = \App\Models\PaymentChannel::where('is_active', true)->orderBy('name')->get();
            return view('transactions.withdrawal', compact('channels'));
        } catch (Throwable $e) {
            Log::error('Transactions@withdrawal failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to open withdrawal form.');
        }
    }

    public function storeWithdrawal(Request $request)
    {
        $rid = uniqid('tx_wd_');
        try {
            $validated = $request->validate([
                'amount'           => 'required|numeric|min:0.01',
                'transaction_date' => 'required|date',
                'method'           => 'required|string|exists:payment_channels,slug',
                'notes'            => 'nullable|string|max:1000',
            ]);

            Transaction::create([
                'type'             => 'debit',
                'amount'           => $validated['amount'],
                'transaction_date' => $validated['transaction_date'],
                'method'           => $validated['method'],
                'notes'            => "Withdrawal (Send to Boss): " . ($validated['notes'] ?? ''),
                'user_id'          => Auth::id(),
            ]);

            return redirect()->route('transactions.index')->with('success', 'Withdrawal recorded successfully.');
        } catch (ValidationException $ve) {
            throw $ve;
        } catch (Throwable $e) {
            Log::error('Transactions@storeWithdrawal failed', ['rid' => $rid, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to record withdrawal. Ref: '.$rid);
        }
    }

    /* =========================
     |  INTERNAL TRANSFER
     |=========================*/
    public function transfer()
    {
        try {
            $channels = \App\Models\PaymentChannel::where('is_active', true)->orderBy('name')->get();
            return view('transactions.transfer', compact('channels'));
        } catch (Throwable $e) {
            Log::error('Transactions@transfer failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to open transfer form.');
        }
    }

    public function storeTransfer(Request $request)
    {
        $rid = uniqid('tx_tr_');
        try {
            $validated = $request->validate([
                'amount'           => 'required|numeric|min:0.01',
                'transaction_date' => 'required|date',
                'from_method'      => 'required|string|exists:payment_channels,slug',
                'to_method'        => 'required|string|exists:payment_channels,slug|different:from_method',
                'notes'            => 'nullable|string|max:1000',
            ]);

            DB::transaction(function () use ($validated) {
                // Debit Source
                Transaction::create([
                    'type'             => 'debit',
                    'amount'           => $validated['amount'],
                    'transaction_date' => $validated['transaction_date'],
                    'method'           => $validated['from_method'],
                    'notes'            => "Transfer OUT to " . ucfirst($validated['to_method']) . ". " . ($validated['notes'] ?? ''),
                    'user_id'          => Auth::id(),
                ]);

                // Credit Destination
                Transaction::create([
                    'type'             => 'credit',
                    'amount'           => $validated['amount'],
                    'transaction_date' => $validated['transaction_date'],
                    'method'           => $validated['to_method'],
                    'notes'            => "Transfer IN from " . ucfirst($validated['from_method']) . ". " . ($validated['notes'] ?? ''),
                    'user_id'          => Auth::id(),
                ]);
            });

            return redirect()->route('transactions.index')->with('success', 'Transfer recorded successfully.');
        } catch (ValidationException $ve) {
            throw $ve;
        } catch (Throwable $e) {
            Log::error('Transactions@storeTransfer failed', ['rid' => $rid, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to record transfer. Ref: '.$rid);
        }
    }
}
