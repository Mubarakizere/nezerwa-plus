<?php

namespace App\Http\Controllers;

use App\Models\{
    Purchase,
    PurchaseItem,
    Product,
    Supplier,
    StockMovement,
    Loan,
    Transaction,
    DebitCredit
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth, Log};
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseController extends Controller
{
    /** Normalize channel; fallback to 'cash'. Accepts minor aliases & trims. */
    protected function channel(?string $ch): string
    {
        $c = strtolower(trim((string)$ch));
        $map = [
            'mo-mo'    => 'momo',
            'mtn'      => 'momo',
            'mtn momo' => 'momo',
        ];
        if (isset($map[$c])) $c = $map[$c];
        return in_array($c, ['cash','bank','momo','mobile_money'], true) ? $c : 'cash';
    }

    /** Read channel from request. Handles legacy forms that sent channel in "method". */
    protected function readChannel(Request $request): string
    {
        $in = $request->input('payment_channel');
        if (!$in) {
            $maybe = strtolower(trim((string)$request->input('method')));
            if (in_array($maybe, ['cash','bank','momo','mobile_money'], true)) {
                $in = $maybe;
            }
        }
        return $this->channel($in);
    }

    /** Derive status from payment vs total. */
    private function statusFromPayment(float $total, float $paid): string
    {
        if ($paid <= 0.0)             return 'pending';
        if ($paid + 0.009 >= $total)  return 'completed';
        return 'partial';
    }

    public function index(Request $request)
    {
        $perPage = in_array((int)$request->get('per_page'), [10,15,25,50,100]) ? (int)$request->get('per_page') : 10;

        $query = Purchase::with(['supplier', 'user'])
            ->withSum('returns as returns_value_sum', 'total_amount')
            ->withSum('returns as returns_refund_sum', 'refund_amount');

        // Search
        if (($search = trim((string) $request->get('search'))) !== '') {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                if (is_numeric($search)) { $q->orWhere('id', (int)$search); }
                $q->orWhere('invoice_number', $like, "%{$search}%")
                  ->orWhere('payment_channel', $like, "%{$search}%")
                  ->orWhere('status', $like, "%{$search}%")
                  ->orWhere('method', $like, "%{$search}%")
                  ->orWhereHas('supplier', fn($s) => $s->where('name', $like, "%{$search}%"));
            });
        }

        // Filters
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_channel')) {
            $query->where('payment_channel', $request->payment_channel);
        }
        if ($request->filled('from')) {
            $query->whereDate('purchase_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('purchase_date', '<=', $request->to);
        }

        $purchases = $query->latest('purchase_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get(['id','name']);

        return view('purchases.index', compact('purchases', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get(['id','name']);
        $products  = Product::orderBy('name')->get(['id','name','cost_price']);
        return view('purchases.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        // Clean empty rows first
        $clean = collect($request->input('products', []))
            ->filter(fn($r) => !empty($r['product_id']) && (float)($r['quantity'] ?? 0) > 0)
            ->values()
            ->toArray();
        $request->merge(['products' => $clean]);

        $request->validate([
            'supplier_id'             => 'required|exists:suppliers,id',
            'purchase_date'           => 'required|date',
            'payment_channel'         => 'nullable|in:cash,bank,momo,mobile_money|required_with:amount_paid',
            'method'                  => 'nullable|string|max:120',
            'notes'                   => 'nullable|string|max:500',
            'tax'                     => 'nullable|numeric|min:0|max:100',
            'discount'                => 'nullable|numeric|min:0|max:100',
            'amount_paid'             => 'nullable|numeric|min:0',
            'products'                => 'required|array|min:1',
            'products.*.product_id'   => 'required|exists:products,id',
            'products.*.quantity'     => 'required|numeric|min:0.01',
            'products.*.unit_cost'    => 'required|numeric|min:0',
        ]);

        if (empty($request->products)) {
            return back()->withErrors(['products' => 'Please add at least one product.'])->withInput();
        }

        try {
            DB::beginTransaction();

            // 1) Totals
            $subtotal = 0.0;
            foreach ($request->products as $row) {
                $subtotal += round(((float)$row['quantity']) * ((float)$row['unit_cost']), 2);
            }

            $taxPct        = (float)($request->tax ?? 0);
            $discountPct   = (float)($request->discount ?? 0);
            $taxValue      = round($subtotal * ($taxPct / 100), 2);
            $discountValue = round($subtotal * ($discountPct / 100), 2);
            $totalAmount   = round(($subtotal + $taxValue) - $discountValue, 2);

            $amountPaidInput = round((float)($request->amount_paid ?? 0), 2);
            $amountPaid      = min($amountPaidInput, $totalAmount);
            $balanceDue      = max(round($totalAmount - $amountPaid, 2), 0);
            $status          = $this->statusFromPayment($totalAmount, $amountPaid);

            $channel = $this->readChannel($request);

            // 2) Header
            $purchase = Purchase::create([
                'supplier_id'     => $request->supplier_id,
                'user_id'         => Auth::id(),
                'purchase_date'   => $request->purchase_date,
                'payment_channel' => $channel,
                'method'          => $request->method,
                'notes'           => $request->notes,
                'subtotal'        => $subtotal,
                'tax'             => $taxValue,
                'discount'        => $discountValue,
                'total_amount'    => $totalAmount,
                'amount_paid'     => $amountPaid,
                'balance_due'     => $balanceDue,
                'status'          => $status,
            ]);

            // 3) Items + movements (track affected products)
            $affectedIds = [];
            foreach ($request->products as $row) {
                $pid       = (int)$row['product_id'];
                $qty       = (float)$row['quantity'];
                $unitCost  = (float)$row['unit_cost'];
                $lineTotal = round($qty * $unitCost, 2);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $pid,
                    'quantity'    => $qty,
                    'unit_cost'   => $unitCost,
                    'total_cost'  => $lineTotal,
                ]);

                if ($product = Product::find($pid)) {
                    // Incremental WAC (fast path)
                    $this->updateWeightedAverageCost($product, $qty, $unitCost);
                }

                StockMovement::create([
                    'product_id'  => $pid,
                    'type'        => 'in',
                    'quantity'    => $qty,
                    'unit_cost'   => $unitCost,
                    'total_cost'  => $lineTotal,
                    'source_type' => Purchase::class,
                    'source_id'   => $purchase->id,
                    'user_id'     => Auth::id(),
                ]);

                $affectedIds[] = $pid;
            }

            // 4) Financials
            if ($amountPaid > 0.009) {
                $notes = "Auto-generated from Purchase #{$purchase->id} (channel: " . strtoupper($channel) . ")";
                if ($purchase->method) {
                    $notes .= " • Ref: {$purchase->method}";
                }

                $txn = Transaction::create([
                    'type'             => 'debit',
                    'user_id'          => $purchase->user_id,
                    'supplier_id'      => $purchase->supplier_id ?? null,
                    'purchase_id'      => $purchase->id,
                    'amount'           => $amountPaid,
                    'transaction_date' => $purchase->purchase_date,
                    'method'           => $channel,
                    'notes'            => $notes,
                ]);

                DebitCredit::create([
                    'type'           => 'debit',
                    'amount'         => $amountPaid,
                    'description'    => "Supplier payment – Purchase #{$purchase->id}",
                    'date'           => now()->toDateString(),
                    'user_id'        => $purchase->user_id,
                    'supplier_id'    => $purchase->supplier_id ?? null,
                    'transaction_id' => $txn->id,
                ]);
            }

            // 5) Loan when not fully paid
            if ($balanceDue > 0.009) {
                Loan::updateOrCreate(
                    ['purchase_id' => $purchase->id],
                    [
                        'user_id'     => $purchase->user_id,
                        'supplier_id' => $purchase->supplier_id ?? null,
                        'type'        => 'taken',
                        'amount'      => $balanceDue,
                        'loan_date'   => $purchase->purchase_date,
                        'status'      => 'pending',
                        'notes'       => "Auto-created for Purchase #{$purchase->id} (Unpaid supplier balance)",
                    ]
                );
            }

            // 6) Recalc WAC from ledger for accuracy
            $this->recalcWacForProducts($affectedIds);

            DB::commit();
            return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase recorded successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Purchase store failed', [
                'error' => $e->getMessage(),
                'payment_channel_input' => $request->input('payment_channel'),
                'method_input' => $request->input('method'),
            ]);
            return back()->withErrors(['error' => 'Purchase failed: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product','items.returnItems', 'transaction', 'user', 'loan']);
        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        $suppliers = Supplier::orderBy('name')->get(['id','name']);
        $products  = Product::orderBy('name')->get(['id','name','cost_price']);
        $purchase->load('items');
        return view('purchases.edit', compact('purchase', 'suppliers', 'products'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        // Clean rows then validate
        $clean = collect($request->input('products', []))
            ->filter(fn($r) => !empty($r['product_id']) && (float)($r['quantity'] ?? 0) > 0)
            ->values()
            ->toArray();
        $request->merge(['products' => $clean]);

        $request->validate([
            'supplier_id'             => 'required|exists:suppliers,id',
            'purchase_date'           => 'required|date',
            'payment_channel'         => 'nullable|in:cash,bank,momo,mobile_money|required_with:amount_paid',
            'method'                  => 'nullable|string|max:120',
            'notes'                   => 'nullable|string|max:500',
            'tax'                     => 'nullable|numeric|min:0|max:100',
            'discount'                => 'nullable|numeric|min:0|max:100',
            'amount_paid'             => 'nullable|numeric|min:0',
            'products'                => 'required|array|min:1',
            'products.*.product_id'   => 'required|exists:products,id',
            'products.*.quantity'     => 'required|numeric|min:0.01',
            'products.*.unit_cost'    => 'required|numeric|min:0',
        ]);

        if (empty($request->products)) {
            return back()->withErrors(['products' => 'Please add at least one product.'])->withInput();
        }

        try {
            DB::beginTransaction();

            // Capture affected product ids BEFORE removing old lines
            $prevIds = $purchase->items()->pluck('product_id')->all();

            // 1) Remove old movements/items
            StockMovement::where('source_type', Purchase::class)
                ->where('source_id', $purchase->id)
                ->delete();
            $purchase->items()->delete();

            // 2) Totals
            $subtotal = 0.0;
            foreach ($request->products as $row) {
                $subtotal += round(((float)$row['quantity']) * ((float)$row['unit_cost']), 2);
            }

            $taxPct        = (float)($request->tax ?? 0);
            $discountPct   = (float)($request->discount ?? 0);
            $taxValue      = round($subtotal * ($taxPct / 100), 2);
            $discountValue = round($subtotal * ($discountPct / 100), 2);
            $totalAmount   = round(($subtotal + $taxValue) - $discountValue, 2);

            $amountPaidInput = round((float)($request->amount_paid ?? 0), 2);
            $amountPaid      = min($amountPaidInput, $totalAmount);
            $balanceDue      = max(round($totalAmount - $amountPaid, 2), 0);
            $status          = $this->statusFromPayment($totalAmount, $amountPaid);

            $channel = $this->readChannel($request);

            // 3) Recreate items + movements
            $newIds = [];
            foreach ($request->products as $row) {
                $pid       = (int)$row['product_id'];
                $qty       = (float)$row['quantity'];
                $unitCost  = (float)$row['unit_cost'];
                $lineTotal = round($qty * $unitCost, 2);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $pid,
                    'quantity'    => $qty,
                    'unit_cost'   => $unitCost,
                    'total_cost'  => $lineTotal,
                ]);

                if ($product = Product::find($pid)) {
                    // Incremental WAC (fast path)
                    $this->updateWeightedAverageCost($product, $qty, $unitCost);
                }

                StockMovement::create([
                    'product_id'  => $pid,
                    'type'        => 'in',
                    'quantity'    => $qty,
                    'unit_cost'   => $unitCost,
                    'total_cost'  => $lineTotal,
                    'source_type' => Purchase::class,
                    'source_id'   => $purchase->id,
                    'user_id'     => Auth::id(),
                ]);

                $newIds[] = $pid;
            }

            // 4) Update header
            $purchase->update([
                'supplier_id'     => $request->supplier_id,
                'purchase_date'   => $request->purchase_date,
                'payment_channel' => $channel,
                'method'          => $request->method,
                'notes'           => $request->notes,
                'subtotal'        => $subtotal,
                'tax'             => $taxValue,
                'discount'        => $discountValue,
                'total_amount'    => $totalAmount,
                'amount_paid'     => $amountPaid,
                'balance_due'     => $balanceDue,
                'status'          => $status,
            ]);

            // 5) Sync Transaction
            $txn = $purchase->transaction;
            if ($amountPaid <= 0.009) {
                if ($txn) {
                    DebitCredit::where('transaction_id', $txn->id)->delete();
                    $txn->delete();
                }
            } else {
                $notes = "Updated from Purchase #{$purchase->id} (channel: " . strtoupper($channel) . ")";
                if ($purchase->method) {
                    $notes .= " • Ref: {$purchase->method}";
                }

                if ($txn) {
                    $txn->update([
                        'amount'           => $amountPaid,
                        'transaction_date' => $purchase->purchase_date,
                        'method'           => $channel,
                        'notes'            => $notes,
                    ]);

                    $dc = DebitCredit::where('transaction_id', $txn->id)->first();
                    if ($dc) {
                        $dc->update([
                            'amount'      => $amountPaid,
                            'description' => "Supplier payment – Purchase #{$purchase->id}",
                            'date'        => now()->toDateString(),
                            'user_id'     => $purchase->user_id,
                            'supplier_id' => $purchase->supplier_id ?? null,
                        ]);
                    } else {
                        DebitCredit::create([
                            'type'           => 'debit',
                            'amount'         => $amountPaid,
                            'description'    => "Supplier payment – Purchase #{$purchase->id}",
                            'date'           => now()->toDateString(),
                            'user_id'        => $purchase->user_id,
                            'supplier_id'    => $purchase->supplier_id ?? null,
                            'transaction_id' => $txn->id,
                        ]);
                    }
                } else {
                    $txn = Transaction::create([
                        'type'             => 'debit',
                        'user_id'          => $purchase->user_id,
                        'supplier_id'      => $purchase->supplier_id ?? null,
                        'purchase_id'      => $purchase->id,
                        'amount'           => $amountPaid,
                        'transaction_date' => $purchase->purchase_date,
                        'method'           => $channel,
                        'notes'            => $notes,
                    ]);

                    DebitCredit::create([
                        'type'           => 'debit',
                        'amount'         => $amountPaid,
                        'description'    => "Supplier payment – Purchase #{$purchase->id}",
                        'date'           => now()->toDateString(),
                        'user_id'        => $purchase->user_id,
                        'supplier_id'    => $purchase->supplier_id ?? null,
                        'transaction_id' => $txn->id,
                    ]);
                }
            }

            // 6) Loan sync
            if ($balanceDue <= 0.009) {
                Loan::where('purchase_id', $purchase->id)->update(['status' => 'paid']);
            } else {
                Loan::updateOrCreate(
                    ['purchase_id' => $purchase->id],
                    [
                        'user_id'     => $purchase->user_id,
                        'supplier_id' => $purchase->supplier_id ?? null,
                        'type'        => 'taken',
                        'amount'      => $balanceDue,
                        'loan_date'   => $purchase->purchase_date,
                        'status'      => 'pending',
                        'notes'       => "Auto-updated for Purchase #{$purchase->id} (Unpaid supplier balance)",
                    ]
                );
            }

            // 7) Recalc WAC from ledger for all impacted products
            $this->recalcWacForProducts(array_merge($prevIds, $newIds));

            DB::commit();
            return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Purchase update failed', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
                'payment_channel_input' => $request->input('payment_channel'),
                'method_input' => $request->input('method'),
            ]);
            return back()->withErrors(['error' => 'Update failed: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(Purchase $purchase)
    {
        try {
            DB::beginTransaction();

            // Capture product ids first
            $affectedIds = $purchase->items()->pluck('product_id')->all();

            if ($purchase->transaction) {
                DebitCredit::where('transaction_id', $purchase->transaction->id)->delete();
                $purchase->transaction->delete();
            }
            Loan::where('purchase_id', $purchase->id)->delete();

            StockMovement::where('source_type', Purchase::class)
                ->where('source_id', $purchase->id)
                ->delete();

            $purchase->items()->delete();
            $purchase->delete();

            // Recalc WAC after removal
            $this->recalcWacForProducts($affectedIds);

            DB::commit();
            return redirect()->route('purchases.index')->with('success', 'Purchase deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Delete failed: ' . $e->getMessage()]);
        }
    }

    /** Weighted Average Cost using pre-inbound stock (fast path for create/update). */
    private function updateWeightedAverageCost(Product $product, float $qtyIn, float $costIn): void
    {
        $currentStock = (float)$product->currentStock();
        $oldCost      = (float)($product->cost_price ?? 0);

        $oldValue = $currentStock * $oldCost;
        $newValue = $qtyIn * $costIn;
        $newQty   = $currentStock + $qtyIn;

        if ($newQty > 0) {
            $product->cost_price = round(($oldValue + $newValue) / $newQty, 2);
            $product->save();
        }
    }

    /**
     * Authoritative WAC recompute from movement ledger.
     * Call after edits/deletes to eliminate drift.
     */
    private function recalcWacForProducts(array $productIds): void
    {
        $ids = array_values(array_unique(array_filter($productIds)));
        if (empty($ids)) return;

        foreach ($ids as $pid) {
            $p = Product::find($pid);
            if (!$p) continue;
            $wac = (float) $p->weightedAverageCost(); // relies on movements
            $p->cost_price = round($wac, 2);
            $p->save();
        }
    }

    public function invoice(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product', 'transaction', 'user', 'loan']);

        $pdf = Pdf::loadView('purchases.invoice', compact('purchase'))
            ->setPaper('a4')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        return $pdf->stream('purchase-invoice-' . $purchase->id . '.pdf');
    }
}
