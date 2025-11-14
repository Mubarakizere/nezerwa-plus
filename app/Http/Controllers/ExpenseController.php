<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    /** List with filters */
    public function index(Request $request)
    {
        // normalize simple inputs
        $request->merge([
            'method' => strtolower((string) $request->input('method', '')),
        ]);

        $perPage = (int) $request->input('per_page', 15);

        $q = Expense::query()
            ->with(['category','supplier','creator'])
            ->between($request->date('from'), $request->date('to'))
            ->category($request->integer('category_id'))
            ->forSupplier($request->integer('supplier_id'))   // <-- renamed scope
            ->method($request->input('method'))
            ->search($request->input('q'))
            ->orderByDesc('date')
            ->orderByDesc('id');

        $expenses  = $q->paginate($perPage);
        $pageTotal = $expenses->getCollection()->sum('amount');

        if ($request->wantsJson()) {
            return response()->json([
                'data'       => $expenses->items(),
                'pagination' => [
                    'current_page' => $expenses->currentPage(),
                    'last_page'    => $expenses->lastPage(),
                    'per_page'     => $expenses->perPage(),
                    'total'        => $expenses->total(),
                ],
                'page_total' => (float) $pageTotal,
            ]);
        }

        return view('expenses.index', [
            'expenses'   => $expenses,
            'categories' => Category::query()
                ->whereIn('kind', ['expense','both'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id','name']),
            'suppliers'  => Supplier::orderBy('name')->get(['id','name']),
            'pageTotal'  => $pageTotal,
        ]);
    }

    /** Create form */
    public function create()
    {
        return view('expenses.create', [
            'categories' => Category::query()
                ->whereIn('kind', ['expense','both'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id','name']),
            'suppliers'  => Supplier::orderBy('name')->get(['id','name']),
            'methods'    => Expense::METHODS,
        ]);
    }

    /** Store */
    public function store(Request $request)
    {
        $request->merge([
            'method' => strtolower((string) $request->input('method', '')),
        ]);

        $data = $request->validate([
            'date'        => ['required','date'],
            'amount'      => ['required','numeric','min:0.01','max:999999999999.99'],
            'category_id' => [
                'required','integer',
                Rule::exists('categories','id')->where(
                    fn ($q) => $q->whereIn('kind',['expense','both'])->where('is_active', true)
                ),
            ],
            'supplier_id' => ['nullable','integer','exists:suppliers,id'],
            'method'      => ['required', Rule::in(Expense::METHODS)],
            'reference'   => ['nullable','string','max:100'],
            'note'        => ['nullable','string'],
        ]);

        $data['created_by'] = auth()->id();

        $expense = Expense::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Created',
                'expense' => $expense->load(['category','supplier']),
            ], 201);
        }

        return redirect()->route('expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    /** Show */
    public function show(Expense $expense)
    {
        $expense->load(['category','supplier','creator']);
        return view('expenses.show', compact('expense'));
    }

    /** Edit form */
    public function edit(Expense $expense)
    {
        return view('expenses.edit', [
            'expense'    => $expense->load(['category','supplier']),
            'categories' => Category::query()
                ->whereIn('kind', ['expense','both'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id','name']),
            'suppliers'  => Supplier::orderBy('name')->get(['id','name']),
            'methods'    => Expense::METHODS,
        ]);
    }

    /** Update */
    public function update(Request $request, Expense $expense)
    {
        $request->merge([
            'method' => strtolower((string) $request->input('method', '')),
        ]);

        $data = $request->validate([
            'date'        => ['required','date'],
            'amount'      => ['required','numeric','min:0.01','max:999999999999.99'],
            'category_id' => [
                'required','integer',
                Rule::exists('categories','id')->where(
                    fn ($q) => $q->whereIn('kind',['expense','both'])->where('is_active', true)
                ),
            ],
            'supplier_id' => ['nullable','integer','exists:suppliers,id'],
            'method'      => ['required', Rule::in(Expense::METHODS)],
            'reference'   => ['nullable','string','max:100'],
            'note'        => ['nullable','string'],
        ]);

        $expense->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Updated',
                'expense' => $expense->load(['category','supplier']),
            ], 200);
        }

        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    /** Destroy */
    public function destroy(Request $request, Expense $expense)
    {
        $expense->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Deleted'], 200);
        }

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }
}
