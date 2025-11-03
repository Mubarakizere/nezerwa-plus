<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;
use Throwable;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $rid = uniqid('tx_index_');
        Log::info('Transactions@index called', ['rid' => $rid, 'query' => $request->all()]);

        try {
            $query = Transaction::with(['user','customer','supplier','sale','purchase']);

            if ($request->filled('type'))         $query->where('type', $request->type);
            if ($request->filled('customer_id'))  $query->where('customer_id', $request->customer_id);
            if ($request->filled('supplier_id'))  $query->where('supplier_id', $request->supplier_id);
            if ($request->filled('date_from'))    $query->whereDate('transaction_date', '>=', $request->date_from);
            if ($request->filled('date_to'))      $query->whereDate('transaction_date', '<=', $request->date_to);

            $transactions = $query->orderByDesc('transaction_date')->paginate(10);
            $totalCredits = Transaction::where('type','credit')->sum('amount');
            $totalDebits  = Transaction::where('type','debit')->sum('amount');

            Log::info('Transactions@index success', [
                'rid' => $rid,
                'count' => $transactions->count(),
                'page' => $transactions->currentPage(),
                'totals' => ['credits' => $totalCredits, 'debits' => $totalDebits],
            ]);

            return view('transactions.index', compact('transactions','totalCredits','totalDebits'));
        } catch (Throwable $e) {
            Log::error('Transactions@index failed', ['rid' => $rid, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Failed to load transactions. Ref: '.$rid);
        }
    }

    public function create()
    {
        $rid = uniqid('tx_create_');
        Log::info('Transactions@create called', ['rid' => $rid]);

        try {
            $customers = Customer::select('id','name')->orderBy('name')->get();
            $suppliers = Supplier::select('id','name')->orderBy('name')->get();
            return view('transactions.create', compact('customers','suppliers'));
        } catch (Throwable $e) {
            Log::error('Transactions@create failed', ['rid' => $rid, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Failed to open create form. Ref: '.$rid);
        }
    }

    public function store(Request $request)
    {
        $rid = uniqid('tx_store_');
        Log::info('Transactions@store called', ['rid' => $rid, 'payload' => $request->all()]);

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

            $validated['user_id'] = auth()->id();
            $tx = Transaction::create($validated);

            Log::info('Transactions@store success', ['rid' => $rid, 'id' => $tx->id]);

            return redirect()->route('transactions.index')->with('success', 'Transaction added successfully.');
        } catch (ValidationException $ve) {
            Log::warning('Transactions@store validation failed', [
                'rid' => $rid,
                'errors' => $ve->errors(),
                'payload' => $request->all(),
            ]);
            throw $ve; // let Laravel redirect back with errors
        } catch (Throwable $e) {
            Log::error('Transactions@store failed', ['rid' => $rid, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Failed to save transaction. Ref: '.$rid);
        }
    }

    public function show(Transaction $transaction)
    {
        $rid = uniqid('tx_show_');
        Log::info('Transactions@show called', ['rid' => $rid, 'id' => $transaction->id]);

        try {
            $transaction->load(['user','customer','supplier','sale','purchase']);
            return view('transactions.show', compact('transaction'));
        } catch (Throwable $e) {
            Log::error('Transactions@show failed', ['rid' => $rid, 'id' => $transaction->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load transaction. Ref: '.$rid);
        }
    }

    public function edit(Transaction $transaction)
    {
        $rid = uniqid('tx_edit_');
        Log::info('Transactions@edit called', ['rid' => $rid, 'id' => $transaction->id]);

        try {
            $customers = Customer::select('id','name')->orderBy('name')->get();
            $suppliers = Supplier::select('id','name')->orderBy('name')->get();
            return view('transactions.edit', compact('transaction','customers','suppliers'));
        } catch (Throwable $e) {
            Log::error('Transactions@edit failed', ['rid' => $rid, 'id' => $transaction->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to open edit form. Ref: '.$rid);
        }
    }

    public function update(Request $request, Transaction $transaction)
    {
        $rid = uniqid('tx_update_');
        Log::info('Transactions@update called', ['rid' => $rid, 'id' => $transaction->id, 'payload' => $request->all()]);

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

            Log::info('Transactions@update success', ['rid' => $rid, 'id' => $transaction->id]);

            return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
        } catch (ValidationException $ve) {
            Log::warning('Transactions@update validation failed', [
                'rid' => $rid,
                'id' => $transaction->id,
                'errors' => $ve->errors(),
            ]);
            throw $ve;
        } catch (Throwable $e) {
            Log::error('Transactions@update failed', ['rid' => $rid, 'id' => $transaction->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Failed to update transaction. Ref: '.$rid);
        }
    }

    public function destroy(Transaction $transaction)
    {
        $rid = uniqid('tx_destroy_');
        Log::info('Transactions@destroy called', ['rid' => $rid, 'id' => $transaction->id]);

        try {
            $transaction->delete();
            Log::info('Transactions@destroy success', ['rid' => $rid, 'id' => $transaction->id]);
            return redirect()->route('transactions.index')->with('success', 'Transaction deleted successfully.');
        } catch (Throwable $e) {
            Log::error('Transactions@destroy failed', ['rid' => $rid, 'id' => $transaction->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Failed to delete transaction. Ref: '.$rid);
        }
    }

    // 🔹 CSV Export (uses storage path to avoid permission issues)
    public function exportCsv()
    {
        $rid = uniqid('tx_csv_');
        Log::info('Transactions@exportCsv called', ['rid' => $rid]);

        try {
            $transactions = Transaction::with(['user','customer','supplier'])->get();
            $filename = 'transactions_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $path = storage_path('app/exports');

            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

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

            Log::info('Transactions@exportCsv success', ['rid' => $rid, 'file' => $fullpath]);
            return response()->download($fullpath)->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            Log::error('Transactions@exportCsv failed', ['rid' => $rid, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Failed to export CSV. Ref: '.$rid);
        }
    }

    public function exportPdf()
    {
        $rid = uniqid('tx_pdf_');
        Log::info('Transactions@exportPdf called', ['rid' => $rid]);

        try {
            $transactions = Transaction::with(['user','customer','supplier'])->get();
            $pdf = Pdf::loadView('transactions.export-pdf', compact('transactions'));
            $name = 'transactions_' . now()->format('Y-m-d_H-i-s') . '.pdf';

            Log::info('Transactions@exportPdf success', ['rid' => $rid, 'records' => $transactions->count()]);
            return $pdf->download($name);
        } catch (Throwable $e) {
            Log::error('Transactions@exportPdf failed', ['rid' => $rid, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Failed to export PDF. Ref: '.$rid);
        }
    }
}
