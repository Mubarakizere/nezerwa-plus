<?php

namespace App\Http\Controllers;

use App\Models\{
    Purchase, PurchaseItem, PurchaseReturn, PurchaseReturnItem,
    Product, StockMovement, Transaction, DebitCredit, Loan
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth, Log, Validator};
use Illuminate\Support\Str;
use Throwable;

class PurchaseReturnController extends Controller
{
    /** Normalize channel; fallback to 'cash'. */
    protected function channel(?string $ch): string
    {
        $c = strtolower(trim((string)$ch));
        $map = ['mo-mo' => 'momo', 'mtn' => 'momo', 'mtn momo' => 'momo'];
        if (isset($map[$c])) $c = $map[$c];
        return in_array($c, ['cash','bank','momo','mobile_money'], true) ? $c : 'cash';
    }

    public function store(Request $request, Purchase $purchase)
    {
        $rid = (string) Str::uuid();
        Log::info('PR.store:start', [
            'rid' => $rid,
            'purchase_id' => $purchase->id,
            'user_id' => Auth::id(),
            'raw_keys' => array_keys($request->all()),
        ]);

        // 1) Gather raw lines from array inputs (REMOVED JSON fallback - use array only)
        $rawLines = $request->input('lines', []);

        if (!is_array($rawLines)) {
            Log::error('PR.store:lines_not_array', ['rid' => $rid, 'type' => gettype($rawLines)]);
            return back()
                ->withErrors(['lines' => 'Invalid return items format.'], 'purchaseReturn')
                ->withInput()
                ->with('open_purchase_return', true);
        }

        // 2) Normalize & keep only positive-qty rows
        $linesClean = collect($rawLines)
            ->filter(function($r) {
                return !empty($r['purchase_item_id'])
                    && !empty($r['product_id'])
                    && isset($r['quantity'])
                    && (float)$r['quantity'] > 0;
            })
            ->values()
            ->all();

        $request->merge(['lines' => $linesClean]);

        Log::debug('PR.store:lines:normalized', [
            'rid' => $rid,
            'raw_lines_count'   => count($rawLines),
            'clean_lines_count' => count($linesClean),
        ]);

        // 3) Validation with friendly messages (named bag: purchaseReturn)
        $messages = [
            'lines.required'    => 'Please add at least one item with a Return Qty greater than 0.',
            'lines.array'       => 'Return items data format is invalid.',
            'lines.min'         => 'Please add at least one item with a Return Qty greater than 0.',

            'lines.*.purchase_item_id.required' => 'Purchase item ID is missing for one or more items.',
            'lines.*.purchase_item_id.exists'   => 'Invalid purchase item selected.',
            'lines.*.product_id.required'       => 'Product ID is missing for one or more items.',
            'lines.*.product_id.exists'         => 'Invalid product selected.',
            'lines.*.quantity.required'         => 'Return quantity is required for all items.',
            'lines.*.quantity.numeric'          => 'Return quantity must be a valid number.',
            'lines.*.quantity.min'              => 'Return quantity must be greater than 0.',
            'lines.*.unit_cost.required'        => 'Unit cost is required for all items.',
            'lines.*.unit_cost.numeric'         => 'Unit cost must be a valid number.',
            'lines.*.unit_cost.min'             => 'Unit cost cannot be negative.',

            'return_date.required'              => 'Return date is required.',
            'return_date.date'                  => 'Return date must be a valid date.',
            'payment_channel.in'                => 'Payment channel must be cash, bank, or momo.',
            'refund_amount.numeric'             => 'Refund amount must be a valid number.',
            'refund_amount.min'                 => 'Refund amount cannot be negative.',
        ];

        $attributes = [
            'return_date'     => 'return date',
            'payment_channel' => 'payment channel',
            'refund_amount'   => 'refund amount',
            'lines'           => 'return items',
        ];

        $v = Validator::make($request->all(), [
            'return_date'               => 'required|date',
            'payment_channel'           => 'nullable|in:cash,bank,momo,mobile_money',
            'method'                    => 'nullable|string|max:120',
            'notes'                     => 'nullable|string|max:500',
            'refund_amount'             => 'nullable|numeric|min:0',

            'lines'                     => 'required|array|min:1',
            'lines.*.purchase_item_id'  => 'required|integer|exists:purchase_items,id',
            'lines.*.product_id'        => 'required|integer|exists:products,id',
            'lines.*.quantity'          => 'required|numeric|min:0.01',
            'lines.*.unit_cost'         => 'required|numeric|min:0',
        ], $messages, $attributes);

        // Only require channel when refund > 0
        $v->after(function($validator) use ($request) {
            $refund  = (float) ($request->input('refund_amount', 0));
            $channel = $request->input('payment_channel');

            if ($refund > 0 && !in_array($channel, ['cash','bank','momo','mobile_money'], true)) {
                $validator->errors()->add(
                    'payment_channel',
                    'Payment channel is required when refund amount is greater than 0.'
                );
            }
        });

        if ($v->fails()) {
            Log::warning('PR.store:validation_failed', [
                'rid' => $rid,
                'errors' => $v->errors()->toArray(),
            ]);

            return back()
                ->withErrors($v, 'purchaseReturn')
                ->withInput()
                ->with('open_purchase_return', true);
        }

        try {
            DB::beginTransaction();

            $purchase->load('items.returnItems', 'supplier', 'user');

            // Per-line validation against remaining
            $total    = 0.0;
            $affected = [];
            $errors = [];

            foreach ($request->lines as $idx => $r) {
                /** @var PurchaseItem|null $pi */
                $pi = $purchase->items->firstWhere('id', (int)$r['purchase_item_id']);

                if (!$pi) {
                    $errors[] = "Purchase item #{$r['purchase_item_id']} not found.";
                    continue;
                }

                if ((int)$pi->product_id !== (int)$r['product_id']) {
                    $errors[] = "Product mismatch for purchase item #{$pi->id}.";
                    continue;
                }

                $already   = (float) $pi->returnItems->sum('quantity');
                $remaining = max((float)$pi->quantity - $already, 0.0);
                $qty       = (float) $r['quantity'];

                if ($qty > $remaining + 0.001) { // Small tolerance for floating point
                    $productName = optional($pi->product)->name ?? "Product #{$pi->product_id}";
                    $errors[] = "Return quantity ({$qty}) exceeds remaining ({$remaining}) for {$productName}.";
                    continue;
                }

                $line = round($qty * (float)$r['unit_cost'], 2);
                $total += $line;
                $affected[] = (int) $pi->product_id;
            }

            // If any line-level errors, return them
            if (!empty($errors)) {
                throw new \RuntimeException(implode(' ', $errors));
            }

            $refundRequested = round((float)($request->refund_amount ?? 0), 2);
            $refund  = min($refundRequested, $total);

            // Validate refund doesn't exceed total
            if ($refund > $total + 0.01) {
                throw new \RuntimeException("Refund amount ({$refund}) cannot exceed total return value ({$total}).");
            }

            $channel = $refund > 0 ? $this->channel($request->input('payment_channel')) : null;

            // Header
            $ret = PurchaseReturn::create([
                'purchase_id'     => $purchase->id,
                'supplier_id'     => $purchase->supplier_id,
                'user_id'         => Auth::id(),
                'return_date'     => $request->return_date,
                'payment_channel' => $channel,
                'method'          => $request->method,
                'notes'           => $request->notes,
                'total_amount'    => $total,
                'refund_amount'   => $refund,
            ]);

            Log::info('PR.store:header_created', [
                'rid' => $rid,
                'return_id' => $ret->id,
                'total' => $total,
                'refund' => $refund,
            ]);

            // Items + Stock OUT
            foreach ($request->lines as $r) {
                $pid = (int)$r['product_id'];
                $qty = (float)$r['quantity'];
                $uc  = (float)$r['unit_cost'];
                $tc  = round($qty * $uc, 2);

                PurchaseReturnItem::create([
                    'purchase_return_id' => $ret->id,
                    'purchase_item_id'   => (int)$r['purchase_item_id'],
                    'product_id'         => $pid,
                    'quantity'           => $qty,
                    'unit_cost'          => $uc,
                    'total_cost'         => $tc,
                ]);

                StockMovement::create([
                    'product_id'  => $pid,
                    'type'        => 'out',
                    'quantity'    => $qty,
                    'unit_cost'   => $uc,
                    'total_cost'  => $tc,
                    'source_type' => PurchaseReturn::class,
                    'source_id'   => $ret->id,
                    'user_id'     => Auth::id(),
                ]);
            }

            // Refund transaction (money in)
            if ($refund > 0) {
                $notes = "Supplier refund – Purchase Return #{$ret->id} (channel: " . strtoupper($channel) . ")";
                if ($ret->method) $notes .= " • Ref: {$ret->method}";

                $txn = Transaction::create([
                    'type'             => 'credit',
                    'user_id'          => $purchase->user_id,
                    'supplier_id'      => $purchase->supplier_id ?? null,
                    'purchase_id'      => $purchase->id,
                    'amount'           => $refund,
                    'transaction_date' => $ret->return_date,
                    'method'           => $channel,
                    'notes'            => $notes,
                ]);

                DebitCredit::create([
                    'type'           => 'credit',
                    'amount'         => $refund,
                    'description'    => "Supplier refund – Purchase Return #{$ret->id}",
                    'date'           => now()->toDateString(),
                    'user_id'        => $purchase->user_id,
                    'supplier_id'    => $purchase->supplier_id ?? null,
                    'transaction_id' => $txn->id,
                ]);

                Log::info('PR.store:refund_txn_created', [
                    'rid' => $rid,
                    'txn_id' => $txn->id,
                    'amount' => $refund,
                ]);
            }

            // Recompute purchase totals + loan
            $this->recomputePurchaseTotals($purchase, $rid);
            $purchase->refresh();

            if (($purchase->balance_due ?? 0) <= 0.009) {
                Loan::where('purchase_id', $purchase->id)->update(['status' => 'paid']);
            } else {
                Loan::updateOrCreate(
                    ['purchase_id' => $purchase->id],
                    [
                        'user_id'     => $purchase->user_id,
                        'supplier_id' => $purchase->supplier_id ?? null,
                        'type'        => 'taken',
                        'amount'      => $purchase->balance_due,
                        'loan_date'   => $purchase->purchase_date,
                        'status'      => 'pending',
                        'notes'       => "Auto-updated for Purchase #{$purchase->id} (Balance after return)",
                    ]
                );
            }

            // WAC resync (local helper)
            $this->recalcWacForProducts($affected);

            DB::commit();

            Log::info('PR.store:success', ['rid' => $rid, 'return_id' => $ret->id]);

            return redirect()
                ->route('purchases.show', $purchase)
                ->with('success', 'Return to supplier recorded successfully!');

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('PR.store:exception', [
                'rid' => $rid,
                'purchase_id' => $purchase->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['error' => 'Return failed: ' . $e->getMessage()], 'purchaseReturn')
                ->withInput()
                ->with('open_purchase_return', true);
        }
    }

    public function destroy(PurchaseReturn $return)
    {
        $rid = (string) Str::uuid();
        Log::info('PR.destroy:start', ['rid' => $rid, 'return_id' => $return->id]);

        try {
            DB::beginTransaction();

            $return->load(['purchase', 'items']);
            $purchase = $return->purchase;

            // Remove refund txn (if any)
            $txn = Transaction::where('purchase_id', $purchase->id)
                ->whereDate('transaction_date', $return->return_date)
                ->where('type', 'credit')
                ->where('amount', $return->refund_amount)
                ->where('method', $return->payment_channel)
                ->first();

            if ($txn) {
                DebitCredit::where('transaction_id', $txn->id)->delete();
                $txn->delete();
            }

            // Delete movements
            StockMovement::where('source_type', PurchaseReturn::class)
                ->where('source_id', $return->id)
                ->delete();

            // Capture affected product ids
            $affected = $return->items->pluck('product_id')->all();

            // Delete items + header
            $return->items()->delete();
            $return->delete();

            // Recompute purchase post-delete
            $this->recomputePurchaseTotals($purchase, $rid);

            // Loan resync
            $purchase->refresh();
            if (($purchase->balance_due ?? 0) <= 0.009) {
                Loan::where('purchase_id', $purchase->id)->update(['status' => 'paid']);
            } else {
                Loan::updateOrCreate(
                    ['purchase_id' => $purchase->id],
                    [
                        'user_id'     => $purchase->user_id,
                        'supplier_id' => $purchase->supplier_id ?? null,
                        'type'        => 'taken',
                        'amount'      => $purchase->balance_due,
                        'loan_date'   => $purchase->purchase_date,
                        'status'      => 'pending',
                        'notes'       => "Auto-updated for Purchase #{$purchase->id} (Balance after return deletion)",
                    ]
                );
            }

            // WAC resync (local helper)
            $this->recalcWacForProducts($affected);

            DB::commit();

            Log::info('PR.destroy:success', ['rid' => $rid]);

            return back()->with('success', 'Return reversed successfully!');

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('PR.destroy:exception', [
                'rid' => $rid,
                'return_id' => $return->id ?? null,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);

            return back()->withErrors(['error' => 'Reverse failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Recompute purchase numbers proportionally to original tax/discount ratio.
     */
    private function recomputePurchaseTotals(Purchase $purchase, ?string $rid = null): void
    {
        $rid = $rid ?: (string) Str::uuid();

        $itemsSubtotal = (float) $purchase->items()->sum('total_cost');
        $returnsSubtotal = (float) PurchaseReturnItem::query()
            ->whereHas('return', fn($q) => $q->where('purchase_id', $purchase->id))
            ->sum('total_cost');

        $newSubtotal = max($itemsSubtotal - $returnsSubtotal, 0);

        $origTaxPct  = ($purchase->subtotal ?? 0) > 0 ? ((float)$purchase->tax / (float)$purchase->subtotal) : 0.0;
        $origDiscPct = ($purchase->subtotal ?? 0) > 0 ? ((float)$purchase->discount / (float)$purchase->subtotal) : 0.0;

        $newTax   = round($newSubtotal * $origTaxPct, 2);
        $newDisc  = round($newSubtotal * $origDiscPct, 2);
        $newTotal = round(($newSubtotal + $newTax) - $newDisc, 2);

        $amountPaid = (float) ($purchase->amount_paid ?? 0);
        $balance    = max(round($newTotal - $amountPaid, 2), 0);

        $status = 'pending';
        if ($amountPaid + 0.009 >= $newTotal) $status = 'completed';
        elseif ($amountPaid > 0.0) $status = 'partial';

        $purchase->update([
            'subtotal'     => $newSubtotal,
            'tax'          => $newTax,
            'discount'     => $newDisc,
            'total_amount' => $newTotal,
            'balance_due'  => $balance,
            'status'       => $status,
        ]);

        Log::info('PR.recompute:done', [
            'rid' => $rid,
            'purchase_id' => $purchase->id,
            'new_subtotal' => $newSubtotal,
            'new_total' => $newTotal,
            'balance' => $balance,
            'status' => $status,
        ]);
    }
    public function note(PurchaseReturn $return)
{
    $return->load(['purchase.supplier','items.product','user']);
    return view('purchases.returns.note', [
        'ret' => $return,
        'purchase' => $return->purchase,
        'supplier' => $return->purchase->supplier,
    ]);
}


    /** Authoritative WAC recompute for a set of product IDs. */
    private function recalcWacForProducts(array $productIds): void
    {
        $ids = array_values(array_unique(array_filter($productIds)));
        if (empty($ids)) return;

        foreach ($ids as $pid) {
            $p = Product::find($pid);
            if (!$p) continue;

            $wac = (float) $p->weightedAverageCost();
            $p->cost_price = round($wac, 2);
            $p->save();

            Log::debug('PR.recalcWAC', [
                'product_id' => $pid,
                'new_wac' => $wac,
            ]);
        }
    }
}
