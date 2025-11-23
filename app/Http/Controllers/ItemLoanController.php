<?php

namespace App\Http\Controllers;

use App\Models\ItemLoan;
use App\Models\ItemLoanReturn;
use App\Models\PartnerCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\StockMovement;
use App\Models\Product;

class ItemLoanController extends Controller
{
    /**
     * GET /inter-loans
     * Filters: search, direction, status, from, to, overdue
     */
    public function index(Request $request)
    {
        $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $loans = ItemLoan::query()
            ->with('partner')
            ->when($request->filled('search'), function ($q) use ($request, $likeOp) {
                $term = '%' . trim($request->string('search')) . '%';
                $q->where(function ($qq) use ($term, $likeOp) {
                    $qq->where('item_name', $likeOp, $term)
                       ->orWhereHas('partner', function ($qp) use ($term, $likeOp) {
                           $qp->where('name', $likeOp, $term);
                       });
                });
            })
            ->when(in_array($request->string('direction'), ['given','taken'], true), function ($q) use ($request) {
                $q->where('direction', $request->string('direction'));
            })
            ->when(in_array($request->string('status'), ['pending','partial','returned','overdue'], true), function ($q) use ($request) {
                $q->where('status', $request->string('status'));
            })
            ->when($request->filled('from'), function ($q) use ($request) {
                $q->whereDate('loan_date', '>=', $request->date('from')->format('Y-m-d'));
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $q->whereDate('loan_date', '<=', $request->date('to')->format('Y-m-d'));
            })
            ->when($request->boolean('overdue'), function ($q) {
                $q->whereNotNull('due_date')
                  ->whereDate('due_date', '<', now()->toDateString())
                  ->whereColumn('quantity', '>', 'quantity_returned');
            })
            ->orderByDesc('loan_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('item_loans.index', [
            'loans'     => $loans,
            'filters'   => $request->only(['search','direction','status','from','to','overdue']),
        ]);
    }

    public function create()
    {
        $partners = PartnerCompany::orderBy('name')->get(['id','name']);
        $products = Product::orderBy('name')->get(['id','name']);
        return view('item_loans.create', compact('partners', 'products'));
    }

    /**
     * On create:
     * - Set status = 'pending' initially (do not mark 'overdue' on the same day even if due_date < today).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'partner_id'  => ['required', Rule::exists('partner_companies', 'id')],
            'product_id'  => ['nullable', Rule::exists('products', 'id')],
            'direction'   => ['required', Rule::in(['given','taken'])],
            'item_name'   => ['required','string','max:255'],
            'unit'        => ['nullable','string','max:20'],
            'quantity'    => ['required','numeric','min:0.01'],
            'loan_date'   => ['required','date'],
            'due_date'    => ['nullable','date','after_or_equal:loan_date'],
            'notes'       => ['nullable','string'],
        ]);

        return DB::transaction(function () use ($data) {
            $loan = new ItemLoan($data);
            $loan->quantity_returned = 0;
            $loan->status = 'pending';
            $loan->user_id = Auth::id();
            $loan->save();

            // Stock Movement
            if ($loan->product_id) {
                $product = Product::find($loan->product_id);
                $cost    = (float)($product->cost_price ?? 0);
                $qty     = (float)$loan->quantity;

                // If GIVEN: we lose stock (OUT)
                // If TAKEN: we gain stock (IN)
                $type = $loan->direction === 'given' ? 'out' : 'in';

                StockMovement::create([
                    'product_id'  => $loan->product_id,
                    'type'        => $type,
                    'quantity'    => $qty,
                    'unit_cost'   => $cost,
                    'total_cost'  => round($qty * $cost, 2),
                    'source_type' => ItemLoan::class,
                    'source_id'   => $loan->id,
                    'user_id'     => Auth::id(),
                ]);
            }

            return redirect()->route('item-loans.show', $loan)->with('success', 'Loan recorded.');
        });
    }

    public function show(ItemLoan $itemLoan)
    {
        $itemLoan->load(['partner', 'returns' => function ($q) {
            $q->orderBy('return_date')->orderBy('id');
        }, 'user']);

        return view('item_loans.show', [
            'loan' => $itemLoan,
        ]);
    }

    public function edit(ItemLoan $itemLoan)
    {
        $partners = PartnerCompany::orderBy('name')->get(['id','name']);
        $hasReturns = $itemLoan->returns()->exists();

        $products = Product::orderBy('name')->get(['id','name']);

        return view('item_loans.edit', [
            'loan'       => $itemLoan,
            'partners'   => $partners,
            'products'   => $products,
            'hasReturns' => $hasReturns,
        ]);
    }

    public function update(Request $request, ItemLoan $itemLoan)
    {
        $hasReturns = $itemLoan->returns()->exists();

        $rules = [
            'partner_id'  => ['required', Rule::exists('partner_companies', 'id')],
            'product_id'  => ['nullable', Rule::exists('products', 'id')],
            'direction'   => ['required', Rule::in(['given','taken'])],
            'item_name'   => ['required','string','max:255'],
            'unit'        => ['nullable','string','max:20'],
            // quantity:
            // - If returns exist, quantity cannot be edited.
            // - Else: numeric >= 0.01; also cannot be less than already returned (should be 0 here).
            'loan_date'   => ['required','date'],
            'due_date'    => ['nullable','date','after_or_equal:loan_date'],
            'notes'       => ['nullable','string'],
        ];

        if ($hasReturns) {
            // Disallow changing quantity once returns exist
            $request->merge(['quantity' => $itemLoan->quantity]); // lock value
        } else {
            $rules['quantity'] = ['required','numeric','min:0.01'];
        }

        $data = $request->validate($rules);

        if (!$hasReturns) {
            // If they reduce quantity below already returned (safety)
            if ((float)$data['quantity'] < (float)$itemLoan->quantity_returned) {
                return back()->withErrors(['quantity' => 'Quantity cannot be less than already returned.'])
                             ->withInput();
            }
        }

        $itemLoan->fill($data);

        // Recalculate status on update (now we can mark overdue if applicable)
        $itemLoan->refreshStatus();
        $itemLoan->save();

        return redirect()->route('item-loans.show', $itemLoan)->with('success', 'Loan updated.');
    }

    /**
     * Safest delete: allow only when NO returns exist.
     * Returns cascade will also remove children but we restrict to ensure audit clarity.
     */
    public function destroy(ItemLoan $itemLoan)
    {
        if ($itemLoan->returns()->exists()) {
            return back()->withErrors(['delete' => 'Cannot delete a loan that has returns.']);
        }

        $itemLoan->delete();

        return redirect()->route('item-loans.index')->with('success', 'Loan deleted.');
    }

    /**
     * POST /inter-loans/{itemLoan}/return
     * Validate returned_qty <= remaining; update header + status.
     */
    public function recordReturn(Request $request, ItemLoan $itemLoan)
    {
        $request->validate([
            'returned_qty' => ['required','numeric','min:0.01'],
            'return_date'  => ['required','date'],
            'note'         => ['nullable','string'],
        ]);

        DB::transaction(function () use ($request, $itemLoan) {
            // Lock the loan row to prevent race conditions
            $loan = ItemLoan::whereKey($itemLoan->id)->lockForUpdate()->first();

            $remaining = (float)$loan->remaining;
            $ret = (float)$request->input('returned_qty');

            if ($ret > $remaining + 1e-9) {
                abort(422, 'Returned quantity exceeds remaining.');
            }

            // Create return line
            $line = new ItemLoanReturn([
                'item_loan_id' => $loan->id,
                'returned_qty' => $ret,
                'return_date'  => $request->date('return_date')->format('Y-m-d'),
                'note'         => $request->input('note'),
                'user_id'      => Auth::id(),
            ]);
            $line->save();

            // Update header totals
            $loan->quantity_returned = (float)$loan->quantity_returned + $ret;

            // Refresh status (may become partial/returned/overdue)
            $loan->refreshStatus();
            $loan->save();

            // Stock Movement for Return
            if ($loan->product_id) {
                $product = Product::find($loan->product_id);
                $cost    = (float)($product->cost_price ?? 0);

                // If loan was GIVEN (stock OUT), return means stock IN
                // If loan was TAKEN (stock IN), return means stock OUT
                $type = $loan->direction === 'given' ? 'in' : 'out';

                StockMovement::create([
                    'product_id'  => $loan->product_id,
                    'type'        => $type,
                    'quantity'    => $ret,
                    'unit_cost'   => $cost,
                    'total_cost'  => round($ret * $cost, 2),
                    'source_type' => ItemLoanReturn::class,
                    'source_id'   => $line->id,
                    'user_id'     => Auth::id(),
                ]);
            }
        });

        return back()->with('success', 'Return recorded.');
    }
}
