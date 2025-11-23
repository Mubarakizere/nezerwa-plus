<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleReturnController extends Controller
{
    public function store(Request $request, Sale $sale)
    {
        $mode = $request->string('mode', 'amount')->toString(); // amount | itemized

        /* ===========================
         * QUICK AMOUNT MODE
         * =========================== */
        if ($mode === 'amount') {
            $data = $request->validate([
                'date'      => ['required','date'],
                'amount'    => ['required','numeric','min:0.01'],
                'method'    => ['nullable', Rule::in(['cash','bank','momo','mobile_money'])],
                'reference' => ['nullable','string','max:255'],
                'reason'    => ['nullable','string','max:2000'],
            ]);

            DB::beginTransaction();
            try {
                $ret = SaleReturn::create([
                    'sale_id'    => $sale->id,
                    'date'       => $data['date'],
                    'amount'     => round((float)$data['amount'], 2),
                    'method'     => $data['method'] ?? null,
                    'reference'  => $data['reference'] ?? null,
                    'reason'     => $data['reason'] ?? null,
                    'created_by' => Auth::id(),
                ]);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Sale return (amount) failed', ['sale_id'=>$sale->id, 'e'=>$e->getMessage()]);
                return back()->withErrors('Could not save return: '.$e->getMessage())->withInput();
            }

            DB::afterCommit(function () use ($sale, $ret, $data) {
                $this->createFinanceTransactionForSaleReturn(
                    $sale, $ret, $ret->amount, $data['date'], $data['method'] ?? null, $data['reference'] ?? null, $data['reason'] ?? null
                );
                $this->recomputeSaleTotals($sale);
            });

            return redirect()->route('sales.show', $sale)->with('success', 'Return recorded.');
        }

        /* ===========================
         * ITEMIZED MODE
         * =========================== */
        $data = $request->validate([
            'date'        => ['required','date'],
            'method'      => ['nullable', Rule::in(['cash','bank','momo','mobile_money'])],
            'reference'   => ['nullable','string','max:255'],
            'reason'      => ['nullable','string','max:2000'],
            'items'       => ['required','array','min:1'],

            'items.*.sale_item_id' => [
                'required','integer',
                Rule::exists('sale_items','id')->where(fn($q) => $q->where('sale_id', $sale->id)),
            ],
            'items.*.quantity'     => ['required','numeric','min:0.01'],
            'items.*.unit_price'   => ['required','numeric','min:0'],
            'items.*.disposition'  => ['required', Rule::in(['restock','writeoff'])],
            'items.*.notes'        => ['nullable','string','max:2000'],
        ]);

        $saleItems    = $sale->items()->select(['id','product_id','quantity','unit_price'])->get()->keyBy('id');
        $requestedIds = collect($data['items'])->pluck('sale_item_id')->unique()->values();

        $alreadyReturned = DB::table('sale_return_items')
            ->select('sale_item_id', DB::raw('COALESCE(SUM(quantity),0) as qty'))
            ->whereIn('sale_item_id', $requestedIds)
            ->groupBy('sale_item_id')
            ->pluck('qty', 'sale_item_id');

        $lines = [];
        $total = 0;

        foreach ($data['items'] as $row) {
            $siId = (int)$row['sale_item_id'];
            $qty  = (float)$row['quantity'];
            $unit = (float)$row['unit_price'];
            $disp = $row['disposition'];

            $si = $saleItems->get($siId);
            if (!$si) {
                throw ValidationException::withMessages(['items' => ["Invalid item selected."]]);
            }

            $max = max(0, (float)$si->quantity - (float)($alreadyReturned[$siId] ?? 0));
            if ($qty > $max + 1e-6) {
                throw ValidationException::withMessages([
                    'items' => ["Requested quantity ({$qty}) exceeds returnable max ({$max}) for product #{$si->product_id}."],
                ]);
            }

            $lineTotal = round($qty * $unit, 2);
            $total    += $lineTotal;

            $lines[] = [
                'sale_item_id' => $siId,
                'product_id'   => $si->product_id,
                'quantity'     => $qty,
                'unit_price'   => $unit,
                'line_total'   => $lineTotal,
                'disposition'  => $disp,
                'notes'        => $row['notes'] ?? null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        if ($total <= 0) {
            throw ValidationException::withMessages(['items' => ['Total must be greater than zero.']]);
        }

        DB::beginTransaction();
        try {
            $ret = SaleReturn::create([
                'sale_id'    => $sale->id,
                'date'       => $data['date'],
                'amount'     => round($total, 2),
                'method'     => $data['method'] ?? null,
                'reference'  => $data['reference'] ?? null,
                'reason'     => $data['reason'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $rows = array_map(function ($r) use ($ret) {
                $r['sale_return_id'] = $ret->id;
                return $r;
            }, $lines);

            SaleReturnItem::insert($rows);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Sale return (itemized) failed', ['sale_id'=>$sale->id, 'e'=>$e->getMessage()]);
            return back()->withErrors('Could not save return items: '.$e->getMessage())->withInput();
        }

        DB::afterCommit(function () use ($sale, $ret, $data) {
            // Finance
            $this->createFinanceTransactionForSaleReturn(
                $sale, $ret, $ret->amount, $data['date'], $data['method'] ?? null, $data['reference'] ?? null, $data['reason'] ?? null
            );

            // Stock (RESTOCK only)
            try {
                $ret->loadMissing('items');
                foreach ($ret->items as $it) {
                    if (($it->disposition ?? 'restock') === 'restock') {
                        $this->createStockMovementForReturnItem($it, $sale, $data['date']);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Stock movement create failed for sale return', ['return_id'=>$ret->id, 'e'=>$e->getMessage()]);
            }

            // Totals/status
            $this->recomputeSaleTotals($sale);
        });

        return redirect()->route('sales.show', $sale)->with('success', 'Return recorded.');
    }

    public function destroy(SaleReturn $return)
    {
        $sale = $return->sale()->first();

        DB::beginTransaction();
        try {
            // Delete related stock movements (created by this controller)
            if (Schema::hasTable('stock_movements')) {
                $itemIds = $return->items()->pluck('id')->all();
                if (!empty($itemIds) && Schema::hasColumn('stock_movements', 'source_type') && Schema::hasColumn('stock_movements', 'source_id')) {
                    DB::table('stock_movements')
                        ->where('source_type', SaleReturnItem::class)
                        ->whereIn('source_id', $itemIds)
                        ->delete();
                }
            }

            // Delete related finance transaction if we saved pointers
            if (Schema::hasTable('transactions') &&
                Schema::hasColumn('transactions', 'reference_type') &&
                Schema::hasColumn('transactions', 'reference_id')) {

                DB::table('transactions')
                    ->where('reference_type', 'sale_return')
                    ->where('reference_id', $return->id)
                    ->delete();
            }

            $return->items()->delete();
            $return->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed deleting sale return', ['return_id'=>$return->id, 'e'=>$e->getMessage()]);
            return back()->withErrors('Could not delete return: '.$e->getMessage());
        }

        DB::afterCommit(function () use ($sale) {
            $this->recomputeSaleTotals($sale);
        });

        return back()->with('success', 'Return deleted.');
    }

    /* =========================================================
     * Helpers
     * ========================================================= */

    protected function createFinanceTransactionForSaleReturn(
        Sale $sale,
        SaleReturn $ret,
        float $amount,
        string $date,
        ?string $method,
        ?string $reference,
        ?string $notes
    ): void {
        try {
            if (!Schema::hasTable('transactions')) return;

            $cols    = Schema::getColumnListing('transactions');
            $allowed = $this->getAllowedTransactionTypes();

            $type = collect(['sale_return','sale_refund','refund','return','credit_note'])
                ->first(fn($t) => in_array($t, $allowed, true))
                ?? ($allowed[0] ?? 'refund');

            $userId = Auth::id()
                ?? ($sale->user_id ?? optional($sale->user)->id)
                ?? (int) (config('app.system_user_id', 1));

            $tx = [
                'type'    => $type,
                'amount'  => round($amount, 2),
                'method'  => $method,
                'user_id' => $userId ?: 1, // never null
            ];

            if (in_array('sale_id', $cols))             $tx['sale_id'] = $sale->id;
            if (in_array('customer_id', $cols))         $tx['customer_id'] = $sale->customer_id ?? null;
            if (in_array('direction', $cols))           $tx['direction'] = 'out';
            if (in_array('reference', $cols))           $tx['reference'] = $reference;
            if (in_array('description', $cols))         $tx['description'] = $notes ?: 'Sale return';
            if (in_array('notes', $cols))               $tx['notes'] = $notes;
            if (in_array('reference_type', $cols))      $tx['reference_type'] = 'sale_return';
            if (in_array('reference_id', $cols))        $tx['reference_id'] = $ret->id;
            if (in_array('transaction_date', $cols))    $tx['transaction_date'] = $date;
            elseif (in_array('date', $cols))            $tx['date'] = $date;

            if (in_array('created_at', $cols))          $tx['created_at'] = now();
            if (in_array('updated_at', $cols))          $tx['updated_at'] = now();

            DB::table('transactions')->insert($tx);
        } catch (\Throwable $e) {
            Log::warning('Transaction failed for sale return', ['sale_id'=>$sale->id, 'e'=>$e->getMessage()]);
        }
    }

    /**
     * Create an IN stock movement for a restocked return item (matches your schema).
     */
    protected function createStockMovementForReturnItem(\App\Models\SaleReturnItem $it, \App\Models\Sale $sale, string $date): void
{
    try {
        // Only RESTOCK goes back to inventory
        if (($it->disposition ?? 'restock') !== 'restock') {
            return;
        }

        $product  = \App\Models\Product::find($it->product_id);
        $unitCost = $product
            ? $product->weightedAverageCost()      // preferred
            : (float)($product->cost_price ?? 0);  // fallback

        $payload = [
            'product_id'  => $it->product_id,
            'type'        => 'in',
            'quantity'    => $it->quantity,
            'unit_cost'   => $unitCost,
            'total_cost'  => round($unitCost * $it->quantity, 2),
            'source_type' => \App\Models\SaleReturnItem::class,
            'source_id'   => $it->id,
            'user_id'     => auth()->id() ?? ($sale->user_id ?? 1),
            'created_at'  => $date,
            'updated_at'  => $date,
        ];

        \App\Models\StockMovement::create($payload);
    } catch (\Throwable $e) {
        \Log::warning('Failed stock movement for sale return item', [
            'item_id' => $it->id,
            'e'       => $e->getMessage(),
        ]);
    }
}

 protected function costBasisForRestock(int $productId, ?string $asOfDate = null): float
{
    try {
        $asOfDate = $asOfDate ?: now()->toDateString();

        // Weighted-average cost from stock_movements IN up to date
        $row = DB::table('stock_movements')
            ->selectRaw('COALESCE(SUM(quantity),0)  as qty')
            ->selectRaw('COALESCE(SUM(quantity * unit_cost),0) as cost')
            ->where('product_id', $productId)
            ->where('type', 'in')
            ->when(Schema::hasColumn('stock_movements', 'occurred_at'),
                fn($q) => $q->where('occurred_at', '<=', $asOfDate),
                fn($q) => $q->whereDate('created_at', '<=', $asOfDate)
            )
            ->first();

        $qty  = (float)($row->qty ?? 0);
        $cost = (float)($row->cost ?? 0);
        if ($qty > 0 && $cost > 0) {
            return round($cost / $qty, 2);
        }

        // Fallback: last purchase unit_cost
        if (Schema::hasTable('purchase_items')) {
            $pi = DB::table('purchase_items')
                ->where('product_id', $productId)
                ->when(Schema::hasColumn('purchase_items', 'created_at'),
                    fn($q) => $q->whereDate('created_at', '<=', $asOfDate)
                )
                ->orderByDesc('id')
                ->first();
            if ($pi && isset($pi->unit_cost)) {
                return round((float)$pi->unit_cost, 2);
            }
        }

        // Fallback: product.cost_price
        $p = DB::table('products')->where('id', $productId)->first();
        return round((float)($p->cost_price ?? 0), 2);
    } catch (\Throwable $e) {
        return 0.0;
    }
}


    /**
     * Read allowed enum values from Postgres CHECK constraint (if present).
     */
    protected function getAllowedTransactionTypes(): array
    {
        try {
            $row = DB::selectOne("
                SELECT pg_get_constraintdef(c.oid) AS def
                FROM pg_constraint c
                JOIN pg_class t ON t.oid = c.conrelid
                WHERE t.relname = 'transactions' AND c.conname = 'transactions_type_check'
                LIMIT 1
            ");
            if (!$row || empty($row->def)) return [];
            // Extract 'literal' values from constraint text
            preg_match_all("/'([^']+)'/", $row->def, $m);
            return isset($m[1]) ? array_values(array_unique($m[1])) : [];
        } catch (\Throwable $e) {
            Log::debug('No type check read', ['e'=>$e->getMessage()]);
            return [];
        }
    }

    /**
     * Recompute sale cached totals/status after any return change.
     */
    protected function recomputeSaleTotals(Sale $sale): void
    {
        try {
            $returns = (float) $sale->returns()->sum('amount');
            $sale->returns_total = round($returns, 2);

            $net  = max(0, (float)$sale->total_amount - $sale->returns_total);
            $paid = (float)($sale->amount_paid ?? 0);

            $sale->status = ($paid + 0.009 >= $net) ? 'completed' : ($paid > 0 ? 'pending' : 'pending');
            $sale->save();
        } catch (\Throwable $e) {
            Log::warning('Failed recomputing sale totals after return', ['sale_id'=>$sale->id, 'e'=>$e->getMessage()]);
        }
    }
}
