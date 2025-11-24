@extends('layouts.app')
@section('title', "Edit Sale #{$sale->id}")

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
            <i data-lucide="edit-3" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <span>Edit Sale #{{ $sale->id }}</span>
        </h1>
        <a href="{{ route('sales.show', $sale) }}" class="btn btn-secondary flex items-center gap-2 text-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Sale
        </a>
    </div>

    {{-- Flash / Errors --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 text-sm space-y-1">
            <strong class="block font-semibold">Error:</strong>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        // 1. Prepare Products Data for Alpine
        // We pass the full product list so Alpine can look up prices/costs.
        $productsJson = $products->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'price' => (float) $p->price,
            'cost' => (float) ($p->cost_price ?? 0),
        ]);

        // 2. Prepare Initial Lines (Products)
        // Priority: old('products') -> $sale->items -> Empty line
        $oldProducts = old('products');
        if ($oldProducts && is_array($oldProducts)) {
            $initialLines = collect($oldProducts)->map(fn($p) => [
                'key' => uniqid(),
                'product_id' => (int) ($p['product_id'] ?? 0),
                'quantity' => (float) ($p['quantity'] ?? 1),
                'unit_price' => (float) ($p['unit_price'] ?? 0),
            ])->values();
        } else {
            $initialLines = $sale->items->map(fn($item) => [
                'key' => uniqid(),
                'product_id' => (int) $item->product_id,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])->values();
        }
        if ($initialLines->isEmpty()) {
            $initialLines = [['key' => uniqid(), 'product_id' => '', 'quantity' => 1, 'unit_price' => 0]];
        }

        // 3. Prepare Initial Payments
        // Priority: old('payments') -> $sale->payments -> $sale->amount_paid (legacy) -> Empty
        $oldPayments = old('payments');
        if ($oldPayments && is_array($oldPayments)) {
            $initialPayments = collect($oldPayments)->map(fn($p) => [
                'key' => uniqid(),
                'method' => $p['method'] ?? 'cash',
                'amount' => (float) ($p['amount'] ?? 0),
                'reference' => $p['reference'] ?? '',
            ])->values();
        } elseif ($sale->payments && $sale->payments->count() > 0) {
            $initialPayments = $sale->payments->map(fn($p) => [
                'key' => uniqid(),
                'method' => $p->method,
                'amount' => (float) $p->amount,
                'reference' => $p->reference,
            ])->values();
        } elseif ((float) $sale->amount_paid > 0) {
            // Fallback for legacy sales with no split payments but an amount_paid
            $initialPayments = collect([[
                'key' => uniqid(),
                'method' => $sale->payment_channel ?? 'cash',
                'amount' => (float) $sale->amount_paid,
                'reference' => $sale->method, // legacy method field was used for ref
            ]]);
        } else {
            $initialPayments = collect([]);
        }

        // 4. Other Init Values
        $custModeInit = old('new_customer_name') ? 'new' : (old('customer_id', $sale->customer_id) ? 'existing' : 'walkin');
        $saleDateInit = old('sale_date', $sale->sale_date ? $sale->sale_date->format('Y-m-d') : date('Y-m-d'));
        $singlePaidInit = old('amount_paid', $sale->amount_paid ?? 0); // Fallback input
    @endphp

    {{-- Form --}}
    <form action="{{ route('sales.update', $sale) }}" method="POST"
          x-data="saleEditForm({
              lines: {{ json_encode($initialLines) }},
              payments: {{ json_encode($initialPayments) }},
              products: {{ json_encode($productsJson) }},
              custMode: '{{ $custModeInit }}',
              saleDate: '{{ $saleDateInit }}',
              singlePaid: {{ $singlePaidInit }}
          })"
          x-init="init()">
        @csrf
        @method('PUT')

        {{-- === Customer & Basic Info === --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5 grid grid-cols-1 md:grid-cols-4 gap-5">

            {{-- Customer (Walk-in / Existing / New) --}}
            <div class="md:col-span-2">
                <x-label value="Customer" />
                <div class="mt-1 grid grid-cols-1 gap-2">
                    <div class="flex items-center gap-2">
                        <label class="inline-flex items-center gap-1 text-xs">
                            <input type="radio" name="cust_mode" value="walkin" x-model="custMode" class="text-indigo-600 border-gray-300"/> Walk-in
                        </label>
                        <label class="inline-flex items-center gap-1 text-xs">
                            <input type="radio" name="cust_mode" value="existing" x-model="custMode" class="text-indigo-600 border-gray-300"/> Existing
                        </label>
                        <label class="inline-flex items-center gap-1 text-xs">
                            <input type="radio" name="cust_mode" value="new" x-model="custMode" class="text-indigo-600 border-gray-300"/> New
                        </label>
                    </div>

                    {{-- Existing --}}
                    <select name="customer_id"
                            x-show="custMode==='existing'"
                            :disabled="custMode!=='existing'"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">Walk-in</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_id', $sale->customer_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>

                    {{-- New inline --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2" x-show="custMode==='new'">
                        <input type="text" name="new_customer_name" placeholder="Customer full name"
                               value="{{ old('new_customer_name') }}"
                               class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <input type="text" name="new_customer_phone" placeholder="Phone (optional)"
                               value="{{ old('new_customer_phone') }}"
                               class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                </div>
            </div>

            {{-- Sale date --}}
            <div>
                <x-label value="Sale Date" />
                <input type="date" name="sale_date"
                       x-model="saleDate"
                       class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       required>
            </div>

            {{-- Invoice-wide reference (optional) --}}
            <div>
                <x-label value="Reference (optional)" />
                <input type="text" name="method"
                       value="{{ old('method', $sale->method) }}"
                       placeholder="POS ref / Txn batch"
                       class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>
        </div>

        {{-- === Product Items === --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm mt-6 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-medium text-gray-800 dark:text-gray-200 flex items-center gap-1">
                    <i data-lucide="package" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                    Products
                </h3>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-outline text-xs sm:text-sm flex items-center gap-1" @click="addLine()">
                        <i data-lucide="plus" class="w-4 h-4"></i> Add Item
                    </button>
                    <button type="button" class="btn btn-outline text-xs sm:text-sm" @click="clearLines()">Clear All</button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs font-medium">
                        <tr>
                            <th class="px-4 py-2 text-left">Product</th>
                            <th class="px-4 py-2 text-right">Qty</th>
                            <th class="px-4 py-2 text-right">Unit Price</th>
                            <th class="px-4 py-2 text-right">Subtotal</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        <template x-for="(row, idx) in lines" :key="row.key">
                            <tr>
                                <td class="px-4 py-2">
                                    <select :name="`products[${idx}][product_id]`"
                                            x-model.number="row.product_id"
                                            @change="onProductChange(row)"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select product</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="text-[11px] mt-1 text-gray-500 dark:text-gray-400" x-show="row.product_id">
                                        <span>Cost:</span>
                                        <span x-text="money(prodCost(row))"></span>
                                        <span class="mx-1">•</span>
                                        <span>Margin:</span>
                                        <span :class="row.unit_price >= prodCost(row) ? 'text-green-600 dark:text-green-400' : 'text-rose-600 dark:text-rose-400'">
                                            <span x-text="marginPct(row)"></span>%
                                        </span>
                                        <button type="button" class="ml-2 underline" @click="resetToDefaultPrice(row)">use default</button>
                                    </div>
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <input type="number" step="0.01" min="0.01"
                                           class="w-20 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-right text-sm"
                                           x-model.number="row.quantity"
                                           :name="`products[${idx}][quantity]`"
                                           @input="recalc()">
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <input type="number" step="0.01" min="0"
                                           class="w-28 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-right text-sm"
                                           x-model.number="row.unit_price"
                                           :name="`products[${idx}][unit_price]`"
                                           @input="recalc()">
                                </td>

                                <td class="px-4 py-2 text-right font-medium text-gray-800 dark:text-gray-200">
                                    <input type="hidden" :name="`products[${idx}][subtotal]`" :value="(row.quantity * row.unit_price).toFixed(2)">
                                    <span x-text="money(row.quantity * row.unit_price)"></span>
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <button type="button" class="btn btn-danger text-xs px-2 py-1" @click="removeLine(idx)">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="!lines.length">
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No items yet. Add your first product.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- === Notes + Payments === --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            {{-- Notes --}}
            <div class="lg:col-span-2">
                <x-label value="Notes" />
                <textarea name="notes" rows="4"
                          class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                          placeholder="Any remarks...">{{ old('notes', $sale->notes) }}</textarea>
            </div>

            {{-- Payment card --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-4 space-y-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100" x-text="money(total)"></span>
                </div>

                {{-- Split payments --}}
                <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Payments</span>
                        <button type="button" class="btn btn-outline btn-sm" @click="addPayment()">
                            <i data-lucide="plus" class="w-4 h-4"></i> Add Method
                        </button>
                    </div>

                    <template x-for="(p, i) in payments" :key="p.key">
                        <div class="grid grid-cols-12 gap-2 items-center mb-2">
                            <div class="col-span-5">
                                <select :name="`payments[${i}][method]`" x-model="p.method"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-sm">
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                    <option value="momo">MoMo</option>
                                    <option value="mobile">Mobile</option>
                                </select>
                            </div>
                            <div class="col-span-4">
                                <input type="number" step="0.01" min="0" placeholder="Amount"
                                       :name="`payments[${i}][amount]`" x-model.number="p.amount"
                                       @input="recalc()"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-right text-sm">
                            </div>
                            <div class="col-span-3">
                                <input type="text" placeholder="Ref / Phone"
                                       :name="`payments[${i}][reference]`" x-model="p.reference"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-sm">
                            </div>
                            <input type="hidden" :name="`payments[${i}][paid_at]`" :value="saleDate">
                            <button type="button" class="col-span-12 text-left text-[11px] text-gray-400 underline"
                                    @click="removePayment(i)">Remove</button>
                        </div>
                    </template>

                    <div x-show="!payments.length" class="text-xs text-gray-500 dark:text-gray-400">
                        No payments added. You can still set a single Amount Paid below, or click “Add Method”.
                    </div>
                </div>

                {{-- Single amount fallback --}}
                <div class="flex justify-between text-sm items-center">
                    <label for="amount_paid" class="text-gray-700 dark:text-gray-300 font-medium">Amount Paid (fallback)</label>
                    <div class="flex items-center gap-2">
                        <input type="number" step="0.01" min="0" id="amount_paid"
                               name="amount_paid"
                               x-model.number="singlePaid"
                               @input="recalc()"
                               class="w-36 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-right text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <button type="button" class="btn btn-outline text-xs px-2 py-1" @click="payFull()">Full</button>
                    </div>
                </div>

                {{-- Totals --}}
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Paid (split + fallback)</span>
                    <span class="font-medium" x-text="money(totalPaid)"></span>
                </div>

                <div class="flex justify-between font-semibold text-gray-800 dark:text-gray-100 border-t border-gray-100 dark:border-gray-700 pt-2">
                    <span>Balance</span>
                    <span :class="(total - totalPaid) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                          x-text="money(Math.max(total - totalPaid, 0))"></span>
                </div>

                {{-- Hidden legacy bindings to keep older logic working --}}
                <input type="hidden" name="payment_channel" :value="dominantMethod()">
                <input type="hidden" name="__paid_from_payments" :value="JSON.stringify(payments)">

                <div class="pt-3 flex flex-col sm:flex-row gap-2">
                    <button type="submit" class="btn btn-primary flex-1 flex items-center justify-center gap-1">
                        <i data-lucide="save" class="w-4 h-4"></i> Update Sale
                    </button>
                    <a href="{{ route('sales.show', $sale) }}" class="btn btn-outline flex-1 flex items-center justify-center gap-1">
                        <i data-lucide="x" class="w-4 h-4"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());

function saleEditForm(config){
    const rid = () => (crypto.randomUUID?.() || (Date.now() + Math.random()));

    return {
        // Data from PHP
        lines: config.lines || [],
        payments: config.payments || [],
        products: config.products || [],
        custMode: config.custMode || 'walkin',
        saleDate: config.saleDate || '',
        singlePaid: Number(config.singlePaid || 0),

        // State
        total: 0,
        totalPaid: 0,
        productMap: {},

        init(){
            // Index products for fast lookup
            this.products.forEach(p => this.productMap[p.id] = p);
            this.recalc();
        },

        money(v){ return Number(v || 0).toFixed(2); },

        // Product Helpers
        prodCost(row){ return this.productMap[row.product_id]?.cost ?? 0; },
        prodDefaultPrice(row){ return this.productMap[row.product_id]?.price ?? 0; },
        marginPct(row){
            const cost = this.prodCost(row);
            const price = Number(row.unit_price || 0);
            if (price <= 0) return 0;
            const margin = price - cost;
            return (margin / price * 100).toFixed(1);
        },
        resetToDefaultPrice(row){
            const p = this.prodDefaultPrice(row);
            if (p > 0) { row.unit_price = p; this.recalc(); }
        },

        // Actions
        addLine(){ this.lines.push({ key: rid(), product_id:'', quantity:1, unit_price:0 }); },
        clearLines(){ this.lines = []; this.recalc(); },
        removeLine(i){ this.lines.splice(i,1); this.recalc(); },

        onProductChange(row){
            const p = this.productMap[row.product_id];
            if (p && (!row.unit_price || row.unit_price === 0)) {
                row.unit_price = p.price;
            }
            this.recalc();
        },

        // Payments
        addPayment(){ this.payments.push({ key: rid(), method: 'cash', amount: 0, reference: '' }); this.recalc(); },
        removePayment(i){ this.payments.splice(i,1); this.recalc(); },

        dominantMethod(){
            if (!this.payments.length) return (this.singlePaid > 0 ? 'cash' : '{{ $sale->payment_channel ?? 'cash' }}');
            const sums = {};
            for (const p of this.payments) sums[p.method] = (sums[p.method] || 0) + Number(p.amount || 0);
            return Object.entries(sums).sort((a,b)=>b[1]-a[1])[0]?.[0] || 'cash';
        },

        payFull(){
            if (this.payments.length === 1) {
                this.payments[0].amount = this.total;
            } else if (this.payments.length > 1) {
                const first = this.payments[0];
                this.payments.slice(1).forEach(p => p.amount = 0);
                first.amount = this.total;
            } else {
                this.singlePaid = this.total;
            }
            this.recalc();
        },

        recalc(){
            this.total = this.lines.reduce((s, r) => s + (Number(r.quantity || 0) * Number(r.unit_price || 0)), 0);
            const splitSum = this.payments.reduce((s,p)=> s + Number(p.amount || 0), 0);
            this.totalPaid = splitSum + Number(this.singlePaid || 0);
        }
    }
}
</script>
@endpush
@endsection
