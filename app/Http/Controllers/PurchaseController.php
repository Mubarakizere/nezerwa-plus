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
    /** Normalize channel; fallback to 'cash'. */
    protected function channel(?string $ch): string
    {
        $c = strtolower((string)$ch);
        return in_array($c, ['cash', 'bank', 'momo'], true) ? $c : 'cash';
    }

    /**
     * List all purchases.
     */
    public function index()
    {
        $purchases = Purchase::with(['supplier', 'user'])
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(10);

        return view('purchases.index', compact('purchases'));
    }

    /**
     * Show form to create a new purchase.
     */
    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get(['id','name']);
        $products  = Product::orderBy('name')->get(['id','name','cost_price']);
        return view('purchases.create', compact('suppliers', 'products'));
    }

    /**
     * Store a new purchase.
     * - Creates stock movements (in)
     * - Creates Transaction (debit) if amount_paid > 0
     * - Creates Loan (taken) if balance remains
     */
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
            'payment_channel'         => 'nullable|in:cash,bank,momo',
            'method'                  => 'nullable|string|max:80',   // human ref (POS/Txn/Cheque)
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
            Log::info('🧾 Creating Purchase...', ['user' => Auth::id()]);

            // 1) Create shell
            $purchase = Purchase::create([
                'supplier_id'     => $request->supplier_id,
                'user_id'         => Auth::id(),
                'purchase_date'   => $request->purchase_date,
                'payment_channel' => $this->channel($request->payment_channel), // new
                'method'          => $request->method,                          // human ref
                'notes'           => $request->notes,

                'subtotal'        => 0,
                'tax'             => $request->tax ?? 0,
                'discount'        => $request->discount ?? 0,
                'total_amount'    => 0,
                'amount_paid'     => $request->amount_paid ?? 0,
                'balance_due'     => 0,
            ]);

            // 2) Items + stock movements (in) + compute subtotal
            $subtotal = 0.0;

            foreach ($request->products as $row) {
                $pid       = (int)$row['product_id'];
                $qty       = (float)$row['quantity'];
                $unitCost  = (float)$row['unit_cost'];
                $lineTotal = round($qty * $unitCost, 2);
                $subtotal += $lineTotal;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $pid,
                    'quantity'    => $qty,
                    'unit_cost'   => $unitCost,
                    'total_cost'  => $lineTotal,
                ]);

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

                // Update Weighted Average Cost after inbound
                if ($product = Product::find($pid)) {
                    $this->updateWeightedAverageCost($product, $qty, $unitCost);
                }
            }

            // 3) Totals
            $taxValue      = round($subtotal * (($purchase->tax ?? 0) / 100), 2);
            $discountValue = round($subtotal * (($purchase->discount ?? 0) / 100), 2);
            $totalAmount   = round(($subtotal + $taxValue) - $discountValue, 2);
            $amountPaid    = round($purchase->amount_paid ?? 0, 2);
            $balanceDue    = round($totalAmount - $amountPaid, 2);

            $purchase->update([
                'subtotal'      => $subtotal,
                'tax'           => $taxValue,
                'discount'      => $discountValue,
                'total_amount'  => $totalAmount,
                'balance_due'   => $balanceDue,
            ]);

            // 4) Financials: Transaction (debit) + DebitCredit (debit)
            if ($amountPaid > 0.009) {
                $notes = "Auto-generated from Purchase #{$purchase->id} (channel: " . strtoupper($purchase->payment_channel ?? 'CASH') . ")";
                if ($purchase->method) {
                    $notes .= " • Ref: {$purchase->method}";
                }

                $txn = Transaction::create([
                    'type'             => 'debit', // cash out
                    'user_id'          => $purchase->user_id,
                    'supplier_id'      => $purchase->supplier_id ?? null, // if your schema has it
                    'purchase_id'      => $purchase->id,
                    'amount'           => $amountPaid,
                    'transaction_date' => $purchase->purchase_date,
                    'method'           => $purchase->payment_channel ?? 'cash', // channel stored here
                    'notes'            => $notes,
                ]);

                DebitCredit::create([
                    'type'           => 'debit',
                    'amount'         => $amountPaid,
                    'description'    => "Supplier payment – Purchase #{$purchase->id}",
                    'date'           => now()->toDateString(),
                    'user_id'        => $purchase->user_id,
                    'supplier_id'    => $purchase->supplier_id ?? null, // if schema supports it
                    'transaction_id' => $txn->id,
                ]);
            }

            // 5) Loan (taken) if not fully paid
            if ($balanceDue > 0.009) {
                Loan::updateOrCreate(
                    ['purchase_id' => $purchase->id],
                    [
                        'user_id'     => $purchase->user_id,
                        'supplier_id' => $purchase->supplier_id ?? null, // ok if column exists; otherwise omit in schema
                        'type'        => 'taken',
                        'amount'      => $balanceDue,
                        'loan_date'   => $purchase->purchase_date,
                        'status'      => 'pending',
                        'notes'       => "Auto-created for Purchase #{$purchase->id} (Unpaid supplier balance)",
                    ]
                );
            }

            DB::commit();
            Log::info('✅ Purchase stored', ['purchase_id' => $purchase->id]);

            return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase recorded successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Purchase store failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Purchase failed: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Weighted Average Cost Calculation (called after each inbound).
     */
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
     * Show purchase details.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product', 'transaction', 'user', 'loan']);
        return view('purchases.show', compact('purchase'));
    }

    /**
     * Edit purchase.
     */
    public function edit(Purchase $purchase)
    {
        $suppliers = Supplier::orderBy('name')->get(['id','name']);
        $products  = Product::orderBy('name')->get(['id','name','cost_price']);
        $purchase->load('items');
        return view('purchases.edit', compact('purchase', 'suppliers', 'products'));
    }

    /**
     * Update purchase (rebuild items + movements; resync financials/loan).
     */
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
            'payment_channel'         => 'nullable|in:cash,bank,momo',
            'method'                  => 'nullable|string|max:80',
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
            Log::info('♻️ Updating Purchase...', ['purchase_id' => $purchase->id]);

            // 1) Clear old stock + items
            StockMovement::where('source_type', Purchase::class)
                ->where('source_id', $purchase->id)
                ->delete();
            $purchase->items()->delete();

            // 2) Rebuild items + movements
            $subtotal = 0.0;
            foreach ($request->products as $row) {
                $pid       = (int)$row['product_id'];
                $qty       = (float)$row['quantity'];
                $unitCost  = (float)$row['unit_cost'];
                $lineTotal = round($qty * $unitCost, 2);
                $subtotal += $lineTotal;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $pid,
                    'quantity'    => $qty,
                    'unit_cost'   => $unitCost,
                    'total_cost'  => $lineTotal,
                ]);

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

                if ($product = Product::find($pid)) {
                    $this->updateWeightedAverageCost($product, $qty, $unitCost);
                }
            }

            // 3) Totals + header
            $taxValue      = round($subtotal * ((float)($request->tax ?? 0) / 100), 2);
            $discountValue = round($subtotal * ((float)($request->discount ?? 0) / 100), 2);
            $totalAmount   = round(($subtotal + $taxValue) - $discountValue, 2);
            $amountPaid    = round((float)($request->amount_paid ?? 0), 2);
            $balanceDue    = round($totalAmount - $amountPaid, 2);

            $purchase->update([
                'supplier_id'     => $request->supplier_id,
                'purchase_date'   => $request->purchase_date,
                'payment_channel' => $this->channel($request->payment_channel),
                'method'          => $request->method,
                'notes'           => $request->notes,

                'subtotal'      => $subtotal,
                'tax'           => $taxValue,
                'discount'      => $discountValue,
                'total_amount'  => $totalAmount,
                'amount_paid'   => $amountPaid,
                'balance_due'   => $balanceDue,
            ]);

            // 4) Sync Transaction (debit)
            $txn = $purchase->transaction; // may be null
            if ($amountPaid <= 0.009) {
                if ($txn) {
                    DebitCredit::where('transaction_id', $txn->id)->delete();
                    $txn->delete();
                }
            } else {
                $notes = "Updated from Purchase #{$purchase->id} (channel: " . strtoupper($purchase->payment_channel ?? 'CASH') . ")";
                if ($purchase->method) {
                    $notes .= " • Ref: {$purchase->method}";
                }

                if ($txn) {
                    $txn->update([
                        'amount'           => $amountPaid,
                        'transaction_date' => $purchase->purchase_date,
                        'method'           => $purchase->payment_channel ?? 'cash',
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
                        'method'           => $purchase->payment_channel ?? 'cash',
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

            // 5) Loan (taken) sync
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

            DB::commit();
            Log::info('✅ Purchase updated', ['purchase_id' => $purchase->id]);

            return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Purchase update failed', ['purchase_id' => $purchase->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Update failed: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Delete purchase (and related stock, txn, debitcredit, loan).
     */
    public function destroy(Purchase $purchase)
    {
        try {
            DB::beginTransaction();

            // Delete financials first
            if ($purchase->transaction) {
                DebitCredit::where('transaction_id', $purchase->transaction->id)->delete();
                $purchase->transaction->delete();
            }
            Loan::where('purchase_id', $purchase->id)->delete();

            // Delete stock + items + header
            StockMovement::where('source_type', Purchase::class)
                ->where('source_id', $purchase->id)
                ->delete();

            $purchase->items()->delete();
            $purchase->delete();

            DB::commit();
            Log::info('🗑️ Purchase deleted', ['purchase_id' => $purchase->id]);

            return redirect()->route('purchases.index')->with('success', 'Purchase deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Purchase delete failed', ['purchase_id' => $purchase->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Delete failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate PDF Invoice.
     */
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
