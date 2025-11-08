<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\{
    Supplier, Purchase, PurchaseReturn, Transaction,
    Customer, Sale, SaleReturn
};
use Carbon\Carbon;

class StatementController extends Controller
{
    /**
     * Supplier Statement (AP)
     */
    public function supplier(Request $request)
    {
        $suppliers = Supplier::orderBy('name')->get();

        $supplierId = (int) $request->input('supplier_id');
        $from       = $request->input('from'); // Y-m-d
        $to         = $request->input('to');   // Y-m-d

        $purchases = collect();
        $txns      = collect();
        $returns   = collect();
        $events    = collect();

        $kpis = [
            'purchases_total' => 0.0, // net after returns (already recomputed in your system)
            'paid_total'      => 0.0, // you paid supplier (transactions.type=debit)
            'refunds_total'   => 0.0, // supplier paid you (transactions.type=credit)
            'balance'         => 0.0,
        ];

        if ($supplierId) {
            // Purchases
            $purchases = Purchase::query()
                ->where('supplier_id', $supplierId)
                ->when($from, fn($q) => $q->whereDate('purchase_date', '>=', $from))
                ->when($to,   fn($q) => $q->whereDate('purchase_date', '<=', $to ?? now()->toDateString()))
                ->orderBy('purchase_date')
                ->get(['id', 'purchase_date', 'total_amount', 'method', 'notes']);

            // Supplier transactions (payments & refunds)
            $txns = Transaction::query()
                ->where('supplier_id', $supplierId)
                ->when($from, fn($q) => $q->whereDate('transaction_date', '>=', $from))
                ->when($to,   fn($q) => $q->whereDate('transaction_date', '<=', $to ?? now()->toDateString()))
                ->orderBy('transaction_date')
                ->get(['id','type','amount','transaction_date','method','notes','purchase_id']);

            // Returns (info only; do not double-count in KPIs)
            $returns = PurchaseReturn::query()
                ->where('supplier_id', $supplierId)
                ->when($from, fn($q) => $q->whereDate('return_date', '>=', $from))
                ->when($to,   fn($q) => $q->whereDate('return_date', '<=', $to ?? now()->toDateString()))
                ->orderBy('return_date')
                ->withCount('items')
                ->get(['id','purchase_id','return_date','payment_channel','method','total_amount','refund_amount']);

            // KPIs
            $kpis['purchases_total'] = (float) $purchases->sum('total_amount');
            $kpis['paid_total']      = (float) $txns->where('type','debit')->sum('amount');
            $kpis['refunds_total']   = (float) $txns->where('type','credit')->sum('amount');
            $kpis['balance']         = round($kpis['purchases_total'] - $kpis['paid_total'] - $kpis['refunds_total'], 2);

            // Timeline
            foreach ($purchases as $p) {
                $events->push([
                    'date'   => Carbon::parse($p->purchase_date ?: now()),
                    'sort'   => 'A',
                    'type'   => 'Purchase',
                    'ref'    => 'Purchase #'.$p->id,
                    'method' => strtoupper($p->method ?? '-'),
                    'amount' => (float) $p->total_amount,
                    'link'   => route('purchases.show', $p->id),
                    'note'   => trim((string)$p->notes),
                ]);
            }

            foreach ($txns as $t) {
                $events->push([
                    'date'   => Carbon::parse($t->transaction_date ?: now()),
                    'sort'   => 'B',
                    'type'   => $t->type === 'debit' ? 'Payment' : 'Supplier Refund',
                    'ref'    => $t->purchase_id ? ('Purchase #'.$t->purchase_id) : ('Txn #'.$t->id),
                    'method' => strtoupper($t->method ?? '-'),
                    'amount' => -1 * (float) $t->amount,
                    'link'   => $t->purchase_id ? route('purchases.show', $t->purchase_id) : null,
                    'note'   => trim((string)$t->notes),
                ]);
            }

            $events = $events->sortBy([['date','asc'], ['sort','asc']])->values();

            $running = 0.0;
            $events  = $events->map(function($row) use (&$running){
                $running += $row['amount'];
                $row['balance'] = round($running, 2);
                return $row;
            });
        }

        return view('reports.suppliers.statement', compact(
            'suppliers','supplierId','from','to','kpis','events','returns'
        ));
    }

    /**
     * Customer Statement (AR)
     * Matches your sale_returns schema: date, amount, method, reference, reason, created_by
     */
    public function customer(Request $request)
    {
        $customers = Customer::orderBy('name')->get();

        $customerId = (int) $request->input('customer_id');
        $from       = $request->input('from'); // Y-m-d
        $to         = $request->input('to');   // Y-m-d

        $sales   = collect();
        $txns    = collect();
        $returns = collect();
        $events  = collect();

        $kpis = [
            'sales_total'   => 0.0, // net after returns (your Sales already recompute)
            'paid_total'    => 0.0, // customer paid you (transactions.type=credit)
            'refunds_total' => 0.0, // you refunded customer (transactions.type=debit)
            'balance'       => 0.0, // AR
        ];

        if ($customerId) {
            // Sales
            $sales = Sale::query()
                ->where('customer_id', $customerId)
                ->when($from, fn($q) => $q->whereDate('sale_date', '>=', $from))
                ->when($to,   fn($q) => $q->whereDate('sale_date', '<=', $to ?? now()->toDateString()))
                ->orderBy('sale_date')
                ->get(['id','sale_date','total_amount','payment_channel','method','notes']);

            // Customer transactions
            $txns = Transaction::query()
                ->where('customer_id', $customerId)
                ->when($from, fn($q) => $q->whereDate('transaction_date', '>=', $from))
                ->when($to,   fn($q) => $q->whereDate('transaction_date', '<=', $to ?? now()->toDateString()))
                ->orderBy('transaction_date')
                ->get(['id','type','amount','transaction_date','method','notes','sale_id']);

            // ---- Customer returns (schema-aware to your migrations) ----
            $saleIds = Sale::where('customer_id', $customerId)->pluck('id');

            // column detection
            $dateCol   = Schema::hasColumn('sale_returns', 'date')
                        ? 'date'
                        : (Schema::hasColumn('sale_returns', 'return_date') ? 'return_date'
                        : (Schema::hasColumn('sale_returns', 'returned_at') ? 'returned_at' : 'created_at'));

            $amountCol = Schema::hasColumn('sale_returns', 'amount')
                        ? 'amount'
                        : (Schema::hasColumn('sale_returns', 'total_amount') ? 'total_amount' : null);

            // select list (always provide the same aliases used by the Blade)
            $selects = ['id','sale_id'];

            // amount + refund_amount
            if ($amountCol) {
                $selects[] = DB::raw("$amountCol as amount");
                // For view compatibility; actual cash refunds are taken from transactions anyway
                $selects[] = DB::raw("$amountCol as refund_amount");
            } else {
                $selects[] = DB::raw("0::numeric as amount");
                $selects[] = DB::raw("0::numeric as refund_amount");
            }

            // method
            if (Schema::hasColumn('sale_returns', 'method')) {
                $selects[] = 'method';
            } else {
                $selects[] = DB::raw("NULL::text as method");
            }

            // payment_channel (not in your schema) -> alias method for UI chips
            if (Schema::hasColumn('sale_returns', 'payment_channel')) {
                $selects[] = 'payment_channel';
            } else {
                $selects[] = DB::raw('(CASE WHEN method IS NULL THEN NULL ELSE method END) as payment_channel');
            }

            // return_date alias
            $selects[] = DB::raw("$dateCol as return_date");

            $returns = class_exists(SaleReturn::class) && $saleIds->isNotEmpty()
                ? SaleReturn::query()
                    ->whereIn('sale_id', $saleIds)
                    ->when($from, fn($q) => $q->whereDate($dateCol, '>=', $from))
                    ->when($to,   fn($q) => $q->whereDate($dateCol, '<=', $to ?? now()->toDateString()))
                    ->orderBy($dateCol)
                    ->withCount('items')
                    ->select($selects)
                    ->get()
                : collect();

            // KPIs
            $kpis['sales_total']   = (float) $sales->sum('total_amount');
            $kpis['paid_total']    = (float) $txns->where('type','credit')->sum('amount'); // customer → you
            $kpis['refunds_total'] = (float) $txns->where('type','debit')->sum('amount');  // you → customer
            $kpis['balance']       = round($kpis['sales_total'] - $kpis['paid_total'] - $kpis['refunds_total'], 2);

            // Timeline
            foreach ($sales as $s) {
                $events->push([
                    'date'   => Carbon::parse($s->sale_date ?: now()),
                    'sort'   => 'A',
                    'type'   => 'Sale',
                    'ref'    => 'Sale #'.$s->id,
                    'method' => strtoupper($s->payment_channel ?? $s->method ?? '-'),
                    'amount' => (float) $s->total_amount, // positive
                    'link'   => route('sales.show', $s->id),
                    'note'   => trim((string)$s->notes),
                ]);
            }

            foreach ($txns as $t) {
                $events->push([
                    'date'   => Carbon::parse($t->transaction_date ?: now()),
                    'sort'   => 'B',
                    'type'   => $t->type === 'credit' ? 'Payment' : 'Customer Refund',
                    'ref'    => $t->sale_id ? ('Sale #'.$t->sale_id) : ('Txn #'.$t->id),
                    'method' => strtoupper($t->method ?? '-'),
                    'amount' => -1 * (float) $t->amount,  // negative for both payments & refunds
                    'link'   => $t->sale_id ? route('sales.show', $t->sale_id) : null,
                    'note'   => trim((string)$t->notes),
                ]);
            }

            $events = $events->sortBy([['date','asc'],['sort','asc']])->values();

            $running = 0.0;
            $events  = $events->map(function($row) use (&$running){
                $running += $row['amount'];
                $row['balance'] = round($running, 2);
                return $row;
            });
        }

        return view('reports.customers.statement', compact(
            'customers','customerId','from','to','kpis','events','returns'
        ));
    }
}
