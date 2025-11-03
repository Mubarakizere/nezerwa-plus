@extends('layouts.app')
@section('title', "Edit Purchase #{$purchase->id}")

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="file-edit" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <span>Edit Purchase #{{ $purchase->id }}</span>
        </h1>
        <a href="{{ route('purchases.index') }}" class="btn btn-secondary flex items-center gap-1 text-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    {{-- Alerts --}}
    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 p-4 rounded-lg">
            <p class="font-semibold mb-1">Please fix the following:</p>
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 p-4 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- Form --}}
    <form action="{{ route('purchases.update', $purchase->id) }}" method="POST"
          x-data="purchaseEditForm()" x-init="init()"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-8">
        @csrf
        @method('PUT')

        {{-- Supplier / Date / Method --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Supplier</label>
                <select name="supplier_id"
                        class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" required>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ $supplier->id == $purchase->supplier_id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Purchase Date</label>
                <input type="date" name="purchase_date"
                       value="{{ \Illuminate\Support\Carbon::parse($purchase->purchase_date)->format('Y-m-d') }}"
                       class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Method</label>
                <select name="method" x-model="method"
                        class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="cash">Cash</option>
                    <option value="bank">Bank</option>
                    <option value="momo">Mobile Money</option>
                </select>

                {{-- Quick toggles --}}
                <div class="flex gap-2 mt-2">
                    <button type="button" @click="setMethod('cash')"  class="px-2 py-1 rounded-md text-[11px] border hover:bg-gray-50 dark:hover:bg-gray-700"
                        :class="badgeClass('cash')">Cash</button>
                    <button type="button" @click="setMethod('bank')"  class="px-2 py-1 rounded-md text-[11px] border hover:bg-gray-50 dark:hover:bg-gray-700"
                        :class="badgeClass('bank')">Bank</button>
                    <button type="button" @click="setMethod('momo')"  class="px-2 py-1 rounded-md text-[11px] border hover:bg-gray-50 dark:hover:bg-gray-700"
                        :class="badgeClass('momo')">MoMo</button>
                </div>
            </div>
        </div>

        {{-- Product rows --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-medium text-gray-800 dark:text-gray-200 flex items-center gap-1">
                    <i data-lucide="clipboard-list" class="w-4 h-4 text-indigo-500"></i>
                    Products
                </h3>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-outline text-xs sm:text-sm" @click="addLine()">
                        <i data-lucide="plus" class="w-4 h-4"></i> Add Product
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
                            <th class="px-4 py-2 text-right">Unit Cost</th>
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
                                            @change="onProductChange(row, $event)"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">-- Select Product --</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}"
                                                    data-cost="{{ $product->cost_price ?? 0 }}">
                                                {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <input type="number" min="1" step="1"
                                           class="w-20 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-right text-sm"
                                           x-model.number="row.quantity"
                                           :name="`products[${idx}][quantity]`"
                                           @input="recalc()">
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <input type="number" step="0.01" min="0"
                                           class="w-28 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-right text-sm"
                                           x-model.number="row.unit_cost"
                                           :name="`products[${idx}][unit_cost]`"
                                           @input="recalc()">
                                </td>

                                <td class="px-4 py-2 text-right font-medium text-gray-800 dark:text-gray-200">
                                    <input type="hidden" :name="`products[${idx}][total_cost]`"
                                           :value="(row.quantity * row.unit_cost).toFixed(2)">
                                    <span x-text="formatMoney(row.quantity * row.unit_cost)"></span>
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <button type="button" class="btn btn-danger text-xs px-2 py-1" @click="removeLine(idx)">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="!lines.length">
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                No items yet. Add your first product.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Totals / Notes --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                <textarea name="notes" rows="3"
                          class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="Any remarks...">{{ old('notes', $purchase->notes) }}</textarea>
            </div>

            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-4 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100" x-text="formatMoney(subtotal)"></span>
                </div>

                <div class="flex justify-between items-center text-sm gap-2">
                    <label class="text-gray-700 dark:text-gray-300 font-medium">Tax (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="tax" x-model.number="taxPercent"
                           class="w-24 text-right rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500" @input="recalc()">
                </div>

                <div class="flex justify-between items-center text-sm gap-2">
                    <label class="text-gray-700 dark:text-gray-300 font-medium">Discount (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="discount" x-model.number="discountPercent"
                           class="w-24 text-right rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500" @input="recalc()">
                </div>

                <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                    <span>Tax Value</span>
                    <span x-text="formatMoney(taxValue)"></span>
                </div>
                <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                    <span>Discount Value</span>
                    <span x-text="formatMoney(discountValue)"></span>
                </div>

                <div class="flex justify-between font-semibold text-gray-800 dark:text-gray-100 border-t border-gray-100 dark:border-gray-700 pt-2">
                    <span>Total</span>
                    <span x-text="formatMoney(grand)"></span>
                </div>

                <div class="flex justify-between items-center text-sm">
                    <label class="text-gray-700 dark:text-gray-300 font-medium">Amount Paid</label>
                    <div class="flex items-center gap-2">
                        <input type="number" step="0.01" min="0" name="amount_paid" x-model.number="paid"
                               class="w-28 text-right rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500" @input="recalc()">
                        <button type="button" class="btn btn-outline text-xs px-2 py-1" @click="payFull()">Full</button>
                    </div>
                </div>

                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Balance</span>
                    <span class="font-medium" :class="balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                          x-text="formatMoney(balance)"></span>
                </div>

                {{-- keep a hidden total if you want to inspect server-side; controller still recomputes --}}
                <input type="hidden" name="total_amount" :value="grand.toFixed(2)">
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" class="btn btn-success flex items-center gap-1">
                <i data-lucide="save" class="w-4 h-4"></i> Update Purchase
            </button>
            <a href="{{ route('purchases.index') }}" class="btn btn-outline flex items-center gap-1">
                <i data-lucide="x" class="w-4 h-4"></i> Cancel
            </a>
        </div>
    </form>
</div>

@php
    // Seed rows safely for Alpine (no nested @json gymnastics)
    $initialLines = $purchase->items->map(fn($i) => [
        'product_id' => (int) $i->product_id,
        'quantity'   => (float) $i->quantity,
        'unit_cost'  => (float) $i->unit_cost,
    ])->values();
    $subtotal = (float) ($purchase->subtotal ?? 0);
    $taxPercentDefault = $subtotal > 0 ? ( (float) ($purchase->tax ?? 0) / $subtotal * 100 ) : 0;
    $discountPercentDefault = $subtotal > 0 ? ( (float) ($purchase->discount ?? 0) / $subtotal * 100 ) : 0;
@endphp

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());

function purchaseEditForm(){
    const initialLines = @json($initialLines);
    return {
        // form state
        method: @json(old('method', $purchase->method ?? 'cash')),
        lines: initialLines.map(r => ({ key: (window.crypto?.randomUUID?.() || (Date.now()+Math.random())), ...r })),

        // money state
        subtotal: 0,
        taxPercent: Number(@json(old('tax', round($taxPercentDefault,2)))),
        discountPercent: Number(@json(old('discount', round($discountPercentDefault,2)))),
        taxValue: 0,
        discountValue: 0,
        grand: Number(@json($purchase->total_amount ?? 0)),
        paid: Number(@json(old('amount_paid', $purchase->amount_paid ?? 0))),
        balance: 0,

        init(){ this.recalc(); },

        setMethod(m){ this.method = m; },
        badgeClass(c){
            const active = this.method === c;
            const base = 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200';
            const on   = {
                cash: 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 border-green-300 dark:border-green-700',
                bank: 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 border-blue-300 dark:border-blue-700',
                momo: 'bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-300 border-purple-300 dark:border-purple-700',
            }[c];
            return active ? on : base;
        },

        addLine(){ this.lines.push({ key: (window.crypto?.randomUUID?.() || (Date.now()+Math.random())), product_id:'', quantity:1, unit_cost:0 }); },
        clearLines(){ this.lines = []; this.recalc(); },
        removeLine(i){ this.lines.splice(i,1); this.recalc(); },

        onProductChange(row, e){
            const cost = Number(e.target.options[e.target.selectedIndex]?.dataset?.cost || 0);
            if (cost > 0 && (!row.unit_cost || row.unit_cost === 0)) row.unit_cost = cost;
            this.recalc();
        },

        recalc(){
            this.subtotal = this.lines.reduce((s,r)=> s + (Number(r.quantity||0) * Number(r.unit_cost||0)), 0);
            this.taxValue = (this.subtotal * Number(this.taxPercent||0)) / 100;
            this.discountValue = (this.subtotal * Number(this.discountPercent||0)) / 100;
            this.grand = Math.max(this.subtotal + this.taxValue - this.discountValue, 0);
            this.balance = Math.max(this.grand - Number(this.paid||0), 0);
        },

        payFull(){ this.paid = this.grand; this.recalc(); },
        formatMoney(v){ return Number(v||0).toFixed(2); }
    }
}
</script>
@endpush
@endsection
