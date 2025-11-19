@php
    $isEdit = isset($purchase);

    // Guard against divide-by-zero
    $taxPct = $discountPct = 0;
    if ($isEdit && ($purchase->subtotal ?? 0) > 0) {
        $taxPct      = round((($purchase->tax ?? 0) / $purchase->subtotal) * 100, 2);
        $discountPct = round((($purchase->discount ?? 0) / $purchase->subtotal) * 100, 2);
    }

    // Initial line items
    $initialLines = collect(old('products',
        $isEdit
            ? $purchase->items->map(fn($i) => [
                'product_id' => (int)   $i->product_id,
                'quantity'   => (float) $i->quantity,
                'unit_cost'  => (float) $i->unit_cost,
            ])->values()->all()
            : []
    ));

    if ($initialLines->isEmpty()) {
        $initialLines = collect([[
            'product_id' => '',
            'quantity'   => 1,
            'unit_cost'  => 0,
        ]]);
    }

    $initialState = [
        'supplier_id'     => old('supplier_id', $isEdit ? $purchase->supplier_id : ''),
        'purchase_date'   => old('purchase_date', $isEdit
            ? ($purchase->purchase_date?->format('Y-m-d') ?? now()->format('Y-m-d'))
            : now()->format('Y-m-d')),
        'payment_channel' => old('payment_channel', $isEdit ? ($purchase->payment_channel ?? 'cash') : 'cash'),
        'method'          => old('method',   $isEdit ? ($purchase->method ?? '')   : ''), // reference
        'notes'           => old('notes',    $isEdit ? ($purchase->notes  ?? '')   : ''),
        'tax'             => (float) old('tax',      $isEdit ? $taxPct      : 0),
        'discount'        => (float) old('discount', $isEdit ? $discountPct : 0),
        'amount_paid'     => (float) old('amount_paid', $isEdit ? ($purchase->amount_paid ?? 0) : 0),
        'lines'           => $initialLines->all(),
    ];
@endphp

<div x-data="purchaseForm(@js($initialState))" x-init="init()" class="space-y-6">
    {{-- Top: supplier, date, channel, reference --}}
    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5 grid grid-cols-1 md:grid-cols-4 gap-5">
        <div>
            <x-label value="Supplier" />
            <select name="supplier_id" x-model="state.supplier_id" class="form-select" required>
                <option value="">-- Select Supplier --</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
            @error('supplier_id')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-label value="Purchase Date" />
            <input type="date" name="purchase_date" x-model="state.purchase_date" class="form-input" required>
            @error('purchase_date')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-label value="Payment Channel" />
            {{-- This is what the controller uses --}}
            <select name="payment_channel" x-model="state.payment_channel" class="form-select">
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
                <option value="momo">MoMo</option>
            </select>
            <div class="flex gap-2 mt-2">
                <button type="button"
                        @click="setChannel('cash')"
                        class="px-2 py-1 rounded-md border"
                        :class="badgeClass('cash')">
                    Cash
                </button>
                <button type="button"
                        @click="setChannel('bank')"
                        class="px-2 py-1 rounded-md border"
                        :class="badgeClass('bank')">
                    Bank
                </button>
                <button type="button"
                        @click="setChannel('momo')"
                        class="px-2 py-1 rounded-md border"
                        :class="badgeClass('momo')">
                    MoMo
                </button>
            </div>
            @error('payment_channel')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-label value="Reference (optional)" />
            {{-- Stored in purchases.method (reference/cheque/txn id) --}}
            <input type="text"
                   name="method"
                   x-model="state.method"
                   class="form-input"
                   placeholder="Invoice # / Txn ID / Cheque no.">
            @error('method')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </section>

    {{-- Items --}}
    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-medium text-gray-800 dark:text-gray-100">Products</h3>
            <div class="flex gap-2">
                <button type="button"
                        class="btn btn-outline text-xs sm:text-sm"
                        @click="addLine()">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Add Product
                </button>
                <button type="button"
                        class="btn btn-outline text-xs sm:text-sm"
                        @click="clearLines()">
                    Clear All
                </button>
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
                    <template x-for="(row, idx) in state.lines" :key="row.key">
                        <tr>
                            {{-- Product --}}
                            <td class="px-4 py-2">
                                <select :name="`products[${idx}][product_id]`"
                                        x-model.number="row.product_id"
                                        @change="onProductChange(row, $event)"
                                        class="form-select">
                                    <option value="">Select product</option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}" data-cost="{{ $p->cost_price ?? 0 }}">
                                            {{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            {{-- Qty --}}
                            <td class="px-4 py-2 text-right">
                                <input type="number"
                                       step="1"
                                       min="1"
                                       class="form-input text-right w-20"
                                       x-model.number="row.quantity"
                                       :name="`products[${idx}][quantity]`"
                                       @input="recalc()">
                            </td>

                            {{-- Unit cost --}}
                            <td class="px-4 py-2 text-right">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       class="form-input text-right w-28"
                                       x-model.number="row.unit_cost"
                                       :name="`products[${idx}][unit_cost]`"
                                       @input="recalc()">
                            </td>

                            {{-- Line total --}}
                            <td class="px-4 py-2 text-right font-medium">
                                <input type="hidden"
                                       :name="`products[${idx}][total_cost]`"
                                       :value="lineTotal(row).toFixed(2)">
                                <span x-text="formatMoney(lineTotal(row))"></span>
                            </td>

                            {{-- Remove --}}
                            <td class="px-4 py-2 text-right">
                                <button type="button"
                                        class="btn btn-danger text-xs px-2 py-1"
                                        @click="removeLine(idx)">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="!state.lines.length">
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            No items yet. Add your first product.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Generic line validation help (optional) --}}
        @if ($errors->has('products'))
            <div class="px-5 py-3 text-xs text-red-600 dark:text-red-400 border-t border-red-200/60 dark:border-red-700/60 bg-red-50/40 dark:bg-red-900/10">
                Some product lines are invalid. Please check quantities, unit costs and selected products.
            </div>
        @endif
    </section>

    {{-- Notes + Totals --}}
    <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Notes --}}
        <div class="md:col-span-2">
            <x-label value="Notes (optional)" />
            <textarea name="notes"
                      x-model="state.notes"
                      rows="4"
                      class="form-textarea"
                      placeholder="Any remarks about this purchase..."></textarea>
            @error('notes')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Totals --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-4 space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                <span class="font-medium" x-text="formatMoney(subtotal)"></span>
            </div>

            <div class="flex items-center justify-between gap-3 text-sm">
                <label class="text-gray-600 dark:text-gray-400">Tax (%)</label>
                <input type="number"
                       step="0.01"
                       min="0"
                       max="100"
                       name="tax"
                       class="form-input text-right w-24"
                       x-model.number="state.tax"
                       @input="recalc()">
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Tax Value</span>
                <span x-text="formatMoney(taxValue)"></span>
            </div>

            <div class="flex items-center justify-between gap-3 text-sm">
                <label class="text-gray-600 dark:text-gray-400">Discount (%)</label>
                <input type="number"
                       step="0.01"
                       min="0"
                       max="100"
                       name="discount"
                       class="form-input text-right w-24"
                       x-model.number="state.discount"
                       @input="recalc()">
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Discount Value</span>
                <span x-text="formatMoney(discountValue)"></span>
            </div>

            <div class="flex justify-between font-semibold border-t pt-2">
                <span>Total</span>
                <span x-text="formatMoney(grand)"></span>
            </div>

            <div class="flex items-center justify-between gap-3 text-sm">
                <label for="amount_paid" class="font-medium">Amount Paid</label>
                <div class="flex items-center gap-2">
                    <input type="number"
                           step="0.01"
                           min="0"
                           id="amount_paid"
                           name="amount_paid"
                           class="form-input text-right w-32"
                           x-model.number="state.amount_paid"
                           @input="recalc()">
                    <button type="button"
                            class="btn btn-outline text-xs px-2 py-1"
                            @click="payFull()">
                        Full
                    </button>
                </div>
            </div>

            <div class="flex justify-between font-semibold border-t pt-2">
                <span>Balance Due</span>
                <span x-text="formatMoney(balance)"></span>
            </div>
        </div>
    </section>

    {{-- Actions --}}
    <div class="pt-2 flex flex-col sm:flex-row gap-2">
        <button type="submit"
                class="btn btn-success flex-1 flex items-center justify-center gap-1">
            <i data-lucide="save" class="w-4 h-4"></i>
            {{ $isEdit ? 'Update Purchase' : 'Save Purchase' }}
        </button>

        <a href="{{ $isEdit ? route('purchases.show', $purchase) : route('purchases.index') }}"
           class="btn btn-outline flex-1 flex items-center justify-center gap-1">
            <i data-lucide="x" class="w-4 h-4"></i>
            Cancel
        </a>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});

function purchaseForm(initial){
    const withKeys = (arr) =>
        arr.map(r => ({
            key: (crypto?.randomUUID?.() || (Date.now() + Math.random())),
            ...r
        }));

    return {
        state: {
            supplier_id:     initial.supplier_id     ?? '',
            purchase_date:   initial.purchase_date   ?? '',
            payment_channel: initial.payment_channel ?? 'cash',
            method:          initial.method          ?? '',
            notes:           initial.notes           ?? '',
            tax:             Number(initial.tax      || 0),
            discount:        Number(initial.discount || 0),
            amount_paid:     Number(initial.amount_paid || 0),
            lines:           withKeys(Array.isArray(initial.lines) && initial.lines.length
                                ? initial.lines
                                : [{ product_id:'', quantity:1, unit_cost:0 }]),
        },

        subtotal: 0,
        taxValue: 0,
        discountValue: 0,
        grand: 0,
        balance: 0,

        init() {
            this.recalc();
        },

        setChannel(c) {
            this.state.payment_channel = c;
        },

        badgeClass(c) {
            const active = this.state.payment_channel === c;
            const base = 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200';
            const on = {
                cash: 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 border-green-300 dark:border-green-700',
                bank: 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 border-blue-300 dark:border-blue-700',
                momo: 'bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-300 border-purple-300 dark:border-purple-700',
            }[c] || base;

            return active ? on : base;
        },

        addLine() {
            this.state.lines.push({
                key: (crypto?.randomUUID?.() || (Date.now() + Math.random())),
                product_id: '',
                quantity: 1,
                unit_cost: 0,
            });
            this.recalc();
        },

        clearLines() {
            this.state.lines = [];
            this.recalc();
        },

        removeLine(i) {
            this.state.lines.splice(i, 1);
            this.recalc();
        },

        onProductChange(row, e) {
            const opt  = e.target.options[e.target.selectedIndex];
            const cost = Number(opt?.dataset?.cost || 0);
            if (cost > 0 && (!row.unit_cost || row.unit_cost === 0)) {
                row.unit_cost = cost;
            }
            this.recalc();
        },

        lineTotal(r) {
            return Number(r.quantity || 0) * Number(r.unit_cost || 0);
        },

        recalc() {
            this.subtotal = this.state.lines.reduce((s, r) => s + this.lineTotal(r), 0);
            this.taxValue = (this.subtotal * Number(this.state.tax || 0)) / 100;
            this.discountValue = (this.subtotal * Number(this.state.discount || 0)) / 100;
            this.grand = Math.max(this.subtotal + this.taxValue - this.discountValue, 0);
            this.balance = Math.max(this.grand - Number(this.state.amount_paid || 0), 0);
        },

        payFull() {
            this.state.amount_paid = this.grand;
            this.recalc();
        },

        formatMoney(v) {
            return Number(v || 0).toFixed(2);
        },
    }
}
</script>
@endpush
