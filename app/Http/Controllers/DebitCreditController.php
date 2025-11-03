<?php

namespace App\Http\Controllers;

use App\Models\DebitCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebitCreditController extends Controller
{
    /**
     * Display a listing of the resource with filters + totals.
     */
    public function index(Request $request)
    {
        $query = DebitCredit::with(['customer', 'supplier', 'user']);

        // Filters
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->query('to'));
        }
        if ($customerId = $request->query('customer_id')) {
            $query->where('customer_id', $customerId);
        }
        if ($supplierId = $request->query('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }
        if ($q = $request->query('q')) {
            $query->where('description', 'like', "%{$q}%");
        }

        // Totals
        $debitsTotal  = (clone $query)->where('type', 'debit')->sum('amount');
        $creditsTotal = (clone $query)->where('type', 'credit')->sum('amount');
        $net = $creditsTotal - $debitsTotal;

        $records = $query->orderByDesc('date')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('debits_credits.index', compact('records', 'debitsTotal', 'creditsTotal', 'net'));
    }

    public function create()
    {
        return view('debits_credits.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:debit,credit',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'transaction_id' => 'nullable|exists:transactions,id',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['date'] = $validated['date'] ?? now()->toDateString();

        DebitCredit::create($validated);

        return redirect()->route('debits-credits.index')->with('success', 'Entry added successfully.');
    }

    public function edit(DebitCredit $debits_credit)
    {
        return view('debits_credits.edit', ['entry' => $debits_credit]);
    }

    public function update(Request $request, DebitCredit $debits_credit)
    {
        $validated = $request->validate([
            'type' => 'required|in:debit,credit',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'date' => 'required|date',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'transaction_id' => 'nullable|exists:transactions,id',
        ]);

        $debits_credit->update($validated);

        return redirect()->route('debits-credits.index')->with('success', 'Entry updated successfully.');
    }

    public function destroy(DebitCredit $debits_credit)
    {
        $debits_credit->delete();
        return redirect()->route('debits-credits.index')->with('success', 'Entry deleted.');
    }
}
