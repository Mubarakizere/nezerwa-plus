@extends('layouts.app')
@section('title', 'Edit Sale')

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

    {{-- Flash messages --}}
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

    {{-- Form --}}
    <form action="{{ route('sales.update', $sale) }}" method="POST" x-data="saleEditForm()" x-init="init()">
        @csrf
        @method('PUT')

        {{-- Customer & Info --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5 grid grid-cols-1 md:grid-cols-4 gap-5">
            <div>
                <x-label value="Customer" />
                <select name="customer_id"
                        class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">Walk-in</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id', $sale->customer_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-label value="Sale Date" />
                <input type="date" name="sale_date"
                       value="{{ old('sale_date', \Illuminate\Support\Carbon::parse($sale->sale_date)->format('Y-m-d')) }}"
                       class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       required>
            </div>

            <div>
                <x-label value="Payment Channel" />
                <select name="payment_channel" x-model="channel"
                        class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="cash" @selected(old('payment_channel', $sale->payment_channel ?? 'cash')==='cash')>Cash</option>
                    <option value="bank" @selected(old('payment_channel', $sale->payment_channel ?? 'cash')==='bank')>Bank</option>
                    <option value="momo" @selected(old('payment_channel', $sale->payment_channel ?? 'cash')==='momo')>MoMo</option>
                </select>
                <div class="flex gap-2 mt-2">
                    <button type="button" @click="setChannel('cash')" class="px-2 py-1 rounded-md text-[11px] border hover:bg-gray-50 dark:hover:bg-gray-700" :class="badgeClass('cash')">Cash</button>
                    <button type="button" @click="setChannel('bank')" class="px-2 py-1 rounded-md text-[11px] border hover:bg-gray-50 dark:hover:bg-gray-700" :class="badgeClass('bank')">Bank</button>
                    <button type="button" @click="setChannel('momo')" class="px-2 py-1 rounded-md text-[11px] border hover:bg-gray-50 dark:hover:bg-gray-700" :class="badgeClass('momo')">MoMo</button>
                </div>
            </div>

            <div>
                <x-label value="Reference (optional)" />
                <input type="text" name="method"
                       value="{{ old('method', $sale->method) }}"
                       placeholder="POS ref / Txn ID / Cheque no."
                       class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>
        </div>

        {{-- Product Items --}}
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
                                            @change="onProductChange(row, $event)"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select product</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}" data-price="{{ $p->price }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <input type="number" step="0.01" min="0.01"
                                           class="w-20 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-right text-sm"
                                           x-model.number="row.quantity"
                                           :name="`products[${idx}][quantity]`" @input="recalc()">
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <input type="number" step="0.01" min="0"
                                           class="w-28 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-right text-sm"
                                           x-model.number="row.unit_price"
                                           :name="`products[${idx}][unit_price]`" @input="recalc()">
                                </td>

                                <td class="px-4 py-2 text-right font-medium text-gray-800 dark:text-gray-200">
                                    <input type="hidden" :name="`products[${idx}][subtotal]`" :value="(row.quantity * row.unit_price).toFixed(2)">
                                    <span x-text="formatMoney(row.quantity * row.unit_price)"></span>
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

        {{-- Payment + Notes --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <div class="md:col-span-2">
                <x-label value="Notes" />
                <textarea name="notes" rows="4"
                          class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                          placeholder="Any remarks...">{{ old('notes', $sale->notes) }}</textarea>
            </div>

            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-4 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100" x-text="formatMoney(total)"></span>
                </div>

                <div class="flex justify-between text-sm items-center">
                    <label for="amount_paid" class="text-gray-700 dark:text-gray-300 font-medium">Amount Paid</label>
                    <div class="flex items-center gap-2">
                        <input type="number" step="0.01" min="0" id="amount_paid"
                               name="amount_paid"
                               value="{{ old('amount_paid', $sale->amount_paid ?? 0) }}"
                               class="w-36 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-right text-sm focus:border-indigo-500 focus:ring-indigo-500"
                               x-model.number="paid" @input="recalc()">
                        <button type="button" class="btn btn-outline text-xs px-2 py-1" @click="payFull()">Full</button>
                    </div>
                </div>

                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Channel</span>
                    <span class="font-medium" x-text="channel.toUpperCase()"></span>
                </div>

                <div class="flex justify-between font-semibold text-gray-800 dark:text-gray-100 border-t border-gray-100 dark:border-gray-700 pt-2">
                    <span>Balance</span>
                    <span x-text="formatMoney(Math.max(total - paid, 0))"></span>
                </div>

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

{{-- Alpine + Lucide --}}
@php
    $mappedItems = old('products')
        ? collect(old('products'))->map(fn($p) => [
            'key'        => uniqid(),
            'product_id' => (int)($p['product_id'] ?? 0),
            'quantity'   => (float)($p['quantity'] ?? 1),
            'unit_price' => (float)($p['unit_price'] ?? 0),
        ])->values()
        : $sale->items->map(fn($i) => [
            'key'        => uniqid(),
            'product_id' => (int) $i->product_id,
            'quantity'   => (float) $i->quantity,
            'unit_price' => (float) $i->unit_price,
        ])->values();
@endphp

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());

function saleEditForm(){
    return {
        channel: '{{ old('payment_channel', $sale->payment_channel ?? 'cash') }}',
        lines: @json($mappedItems),
        total: 0,
        paid: Number('{{ old('amount_paid', $sale->amount_paid ?? 0) }}'),

        init(){ if(!this.lines.length){ this.addLine(); } this.recalc(); },
        setChannel(c){ this.channel = c; },
        badgeClass(c){
            const active = this.channel===c;
            const base = 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200';
            const on   = {
                cash: 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 border-green-300 dark:border-green-700',
                bank: 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 border-blue-300 dark:border-blue-700',
                momo: 'bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-300 border-purple-300 dark:border-purple-700',
            }[c];
            return active ? on : base;
        },

        addLine(){
            this.lines.push({
                key: crypto.randomUUID?.() || (Date.now()+Math.random()),
                product_id: '',
                quantity: 1,
                unit_price: 0
            });
        },
        clearLines(){ this.lines = []; this.recalc(); },
        removeLine(i){ this.lines.splice(i, 1); this.recalc(); },

        onProductChange(row, event){
            const price = Number(event.target.options[event.target.selectedIndex]?.dataset?.price || 0);
            if (price > 0 && (!row.unit_price || row.unit_price === 0)) {
                row.unit_price = price;
            }
            this.recalc();
        },

        recalc(){
            this.total = this.lines.reduce((sum, r) =>
                sum + (Number(r.quantity||0) * Number(r.unit_price||0)), 0);
        },

        payFull(){ this.paid = this.total; },
        formatMoney(v){ return Number(v || 0).toFixed(2); }
    }
}
</script>
@endpush
@endsection
