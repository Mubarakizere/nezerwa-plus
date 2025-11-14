<?php

namespace App\Http\Controllers;

use App\Models\DebitCredit;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DebitCreditController extends Controller
{
    /**
     * List entries with lightweight filters and filter-aware totals.
     */
    public function index(Request $request)
    {
        $isPg = DB::getDriverName() === 'pgsql';
        $like = $isPg ? 'ilike' : 'like';

        $base = DebitCredit::query()
            ->with(['customer','supplier','user'])
            ->when($request->filled('type'),        fn($q) => $q->where('type', $request->type))
            ->when($request->filled('from'),        fn($q) => $q->whereDate('date', '>=', $request->from))
            ->when($request->filled('to'),          fn($q) => $q->whereDate('date', '<=', $request->to))
            ->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('supplier_id'), fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('q'), function ($q) use ($request, $like) {
                $term = trim($request->q);
                $q->where(function ($xx) use ($term, $like) {
                    $xx->where('description', $like, "%{$term}%")
                       ->orWhereHas('customer', fn($cq) => $cq->where('name', $like, "%{$term}%"))
                       ->orWhereHas('supplier', fn($sq) => $sq->where('name', $like, "%{$term}%"))
                       ->orWhereHas('user',     fn($uq) => $uq->where('name', $like, "%{$term}%"));
                });
            });

        // Filter-aware totals
        $debitsTotal  = (clone $base)->where('type', 'debit')->sum('amount');
        $creditsTotal = (clone $base)->where('type', 'credit')->sum('amount');
        $net          = $creditsTotal - $debitsTotal;

        // Page data
        $records = (clone $base)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('debits_credits.index', compact('records', 'debitsTotal', 'creditsTotal', 'net'));
    }

    /**
     * Show create form (with party lists).
     */
    public function create()
    {
        $customers = Customer::select('id','name')->orderBy('name')->get();
        $suppliers = Supplier::select('id','name')->orderBy('name')->get();

        return view('debits_credits.create', compact('customers','suppliers'));
    }

    /**
     * Persist a new entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'        => ['required', Rule::in(['debit','credit'])],
            'amount'      => ['required','numeric','min:0.01'],
            'description' => ['nullable','string','max:255'],
            'date'        => ['nullable','date'],
            // Party guard: cannot set both
            'customer_id' => ['nullable','exists:customers,id','prohibited_with:supplier_id'],
            'supplier_id' => ['nullable','exists:suppliers,id','prohibited_with:customer_id'],
            // 1:1 with transactions (your migration added a unique index)
            'transaction_id' => ['nullable','exists:transactions,id','unique:debits_credits,transaction_id'],
        ]);

        $validated['user_id'] = Auth::id();
        $validated['date']    = $validated['date'] ?? now()->toDateString();

        DebitCredit::create($validated);

        return redirect()
            ->route('debits-credits.index')
            ->with('success', 'Entry added successfully.');
    }

    /**
     * Show edit form (with party lists).
     */
    public function edit(DebitCredit $debits_credit)
    {
        $customers = Customer::select('id','name')->orderBy('name')->get();
        $suppliers = Supplier::select('id','name')->orderBy('name')->get();

        return view('debits_credits.edit', [
            'entry'     => $debits_credit,
            'customers' => $customers,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Update an existing entry.
     */
    public function update(Request $request, DebitCredit $debits_credit)
    {
        $validated = $request->validate([
            'type'        => ['required', Rule::in(['debit','credit'])],
            'amount'      => ['required','numeric','min:0.01'],
            'description' => ['nullable','string','max:255'],
            'date'        => ['required','date'],
            'customer_id' => ['nullable','exists:customers,id','prohibited_with:supplier_id'],
            'supplier_id' => ['nullable','exists:suppliers,id','prohibited_with:customer_id'],
            // keep 1:1 unique but ignore current row
            'transaction_id' => [
                'nullable',
                'exists:transactions,id',
                Rule::unique('debits_credits','transaction_id')->ignore($debits_credit->id),
            ],
        ]);

        $debits_credit->update($validated);

        return redirect()
            ->route('debits-credits.index')
            ->with('success', 'Entry updated successfully.');
    }

    /**
     * Delete an entry.
     */
    public function destroy(DebitCredit $debits_credit)
    {
        $debits_credit->delete();

        return redirect()
            ->route('debits-credits.index')
            ->with('success', 'Entry deleted.');
    }
}
