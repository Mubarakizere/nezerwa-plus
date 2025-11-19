@once
    @push('scripts')
        <script>
            // Global factory for the Purchase Return modal
            window.purchaseReturnForm = function (ctx) {
                return {
                    open: false,
                    lines: [],
                    form: {},
                    total: 0,

                    init() {
                        // Build lines with safe numeric defaults
                        this.lines = (ctx.lines || []).map(r => ({
                            purchase_item_id: r.purchase_item_id,
                            product_id:       r.product_id,
                            product_name:     r.product_name,
                            purchased_qty:    Number(r.purchased_qty || 0),
                            returned_qty:     Number(r.returned_qty || 0),
                            remaining:        Number(r.remaining || 0),
                            return_qty:       0,
                            unit_cost:        Number(r.unit_cost || 0),
                        }));

                        this.form = {
                            return_date:     ctx.initial?.return_date ?? '',
                            payment_channel: ctx.initial?.payment_channel ?? '',
                            method:          ctx.initial?.method ?? '',
                            notes:           ctx.initial?.notes ?? '',
                            refund_amount:   Number(ctx.initial?.refund_amount || 0),
                        };

                        this.recalc();

                        // Auto-open ONLY when validation errors exist
                        if (ctx.shouldOpen === true) {
                            this.open = true;
                        }
                    },

                    handleOpen() {
                        this.open = true;
                    },

                    closeModal() {
                        this.open = false;
                    },

                    fmt(v) {
                        return Number(v || 0).toFixed(2);
                    },

                    money(v) {
                        return Number(v || 0).toFixed(2);
                    },

                    rowError(i) {
                        const r = this.lines[i];
                        if (!r) return false;

                        const q   = Number(r.return_qty || 0);
                        const uc  = Number(r.unit_cost || 0);
                        const rem = Number(r.remaining || 0);

                        // Only show error if qty > 0 and exceeds remaining or unit cost is negative
                        if (q > 0 && (q > rem + 0.001 || uc < 0)) {
                            return true;
                        }
                        return false;
                    },

                    get valid() {
                        // Must have lines
                        if (!this.lines || this.lines.length === 0) return false;

                        // At least one positive qty
                        const hasPositiveQty = this.lines.some(r => Number(r.return_qty || 0) > 0);
                        if (!hasPositiveQty) return false;

                        // No row-level errors
                        for (let i = 0; i < this.lines.length; i++) {
                            if (this.rowError(i)) return false;
                        }

                        // Refund cannot exceed total
                        const refund = Number(this.form.refund_amount || 0);
                        if (refund > this.total + 0.01) return false;

                        // If refund > 0, must have payment channel
                        if (refund > 0 && !this.form.payment_channel) return false;

                        return true;
                    },

                    recalc() {
                        this.total = this.lines.reduce((sum, r) => {
                            const qty  = Number(r.return_qty || 0);
                            const cost = Number(r.unit_cost || 0);
                            return sum + (qty * cost);
                        }, 0);

                        this.clampRefund();
                    },

                    clampRefund() {
                        const t = Number(this.total || 0);
                        let r   = Number(this.form.refund_amount || 0);

                        if (r < 0) r = 0;
                        if (r > t) r = t;

                        this.form.refund_amount = r;
                    },
                };
            };
        </script>
    @endpush
@endonce

@php
    // Build rows for the table (only items with remaining > 0)
    $rows = $purchase->items->map(function ($i) {
        $returned  = (float) ($i->returnItems->sum('quantity') ?? 0);
        $remaining = max((float) $i->quantity - $returned, 0);

        return [
            'purchase_item_id' => $i->id,
            'product_id'       => $i->product_id,
            'product_name'     => $i->product->name ?? ('#' . $i->product_id),
            'purchased_qty'    => (float) $i->quantity,
            'returned_qty'     => $returned,
            'remaining'        => $remaining,
            'unit_cost'        => (float) $i->unit_cost,
        ];
    })->filter(fn ($r) => $r['remaining'] > 0)->values();

    // Only auto-open when there are validation errors from the purchaseReturn bag
    $hasValidationErrors = $errors->purchaseReturn->any();
    $shouldOpen          = $hasValidationErrors;

    $modalContext = [
        'lines'      => $rows->toArray(),
        'initial'    => [
            'return_date'     => old('return_date', now()->format('Y-m-d')),
            'payment_channel' => old('payment_channel', 'cash'),
            'method'          => old('method', ''),
            'notes'           => old('notes', ''),
            'refund_amount'   => (float) old('refund_amount', 0),
        ],
        'shouldOpen' => $shouldOpen,
    ];
@endphp

<style>[x-cloak]{display:none!important}</style>

<div
    id="purchase-return-modal"
    x-data="purchaseReturnForm(@js($modalContext))"
    x-init="init()"
    x-cloak
    x-show="open"
    x-trap.noscroll="open"
    @open-purchase-return.window="handleOpen()"
    @keydown.escape.window="closeModal()"
    class="fixed inset-0 z-50 flex items-center justify-center"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/40"
         x-show="open"
         x-transition.opacity
         @click="closeModal()"></div>

    {{-- Modal --}}
    <form method="POST"
          action="{{ route('purchases.returns.store', $purchase) }}"
          class="relative w-full max-w-5xl bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-5 max-h-[90vh] overflow-y-auto"
          x-show="open"
          x-transition.scale.origin.center>
        @csrf

        <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Return to Supplier</h3>
            <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        {{-- Validation errors (purchaseReturn bag) --}}
        @if ($errors->purchaseReturn->any())
            <div class="rounded-lg border-2 border-red-400 bg-red-50 text-red-800 text-sm px-4 py-3">
                <div class="font-semibold mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                              clip-rule="evenodd"></path>
                    </svg>
                    Please fix the following errors:
                </div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->purchaseReturn->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- All items already fully returned --}}
        @if($rows->isEmpty())
            <div class="rounded-lg border-2 border-yellow-400 bg-yellow-50 text-yellow-800 text-sm px-4 py-3">
                <div class="font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                              clip-rule="evenodd"></path>
                    </svg>
                    No Items Available for Return
                </div>
                <p class="mt-1">
                    All items from this purchase have already been fully returned.
                </p>
            </div>
        @endif

        {{-- Top fields --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">
                    Return Date <span class="text-red-500">*</span>
                </label>
                <input type="date"
                       name="return_date"
                       x-model="form.return_date"
                       class="form-input w-full @error('return_date','purchaseReturn') border-red-500 @enderror"
                       required>
                @error('return_date', 'purchaseReturn')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">
                    Payment Channel <span class="text-gray-500 text-xs">(if refund)</span>
                </label>
                <select name="payment_channel"
                        x-model="form.payment_channel"
                        class="form-select w-full @error('payment_channel','purchaseReturn') border-red-500 @enderror"
                        :disabled="Number(form.refund_amount || 0) <= 0">
                    <option value="">Select...</option>
                    <option value="cash">Cash</option>
                    <option value="bank">Bank</option>
                    <option value="momo">MoMo</option>
                </select>
                @error('payment_channel', 'purchaseReturn')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Reference</label>
                <input type="text"
                       name="method"
                       x-model="form.method"
                       class="form-input w-full"
                       placeholder="Transaction / Reference # (optional)">
            </div>
        </div>

        {{-- Items table --}}
        <div class="overflow-x-auto border rounded-lg">
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs font-medium">
                    <tr>
                        <th class="px-4 py-3 text-left">Product</th>
                        <th class="px-4 py-3 text-right">Purchased</th>
                        <th class="px-4 py-3 text-right">Already Returned</th>
                        <th class="px-4 py-3 text-right">Remaining</th>
                        <th class="px-4 py-3 text-right">
                            Return Qty <span class="text-red-500">*</span>
                        </th>
                        <th class="px-4 py-3 text-right">
                            Unit Cost <span class="text-red-500">*</span>
                        </th>
                        <th class="px-4 py-3 text-right">Line Total</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                    <template x-for="(row, idx) in lines" :key="row.purchase_item_id">
                        <tr :class="rowError(idx) ? 'bg-red-50 dark:bg-red-900/20' : ''">
                            <td class="px-4 py-3">
                                {{-- Hidden fields posted per row --}}
                                <input type="hidden" :name="`lines[${idx}][purchase_item_id]`" :value="row.purchase_item_id">
                                <input type="hidden" :name="`lines[${idx}][product_id]`" :value="row.product_id">
                                <span x-text="row.product_name"
                                      class="font-medium text-gray-900 dark:text-gray-100"></span>
                            </td>

                            <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400"
                                x-text="fmt(row.purchased_qty)"></td>
                            <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400"
                                x-text="fmt(row.returned_qty)"></td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-100"
                                x-text="fmt(row.remaining)"></td>

                            <td class="px-4 py-3 text-right">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       :max="row.remaining"
                                       class="form-input text-right w-28"
                                       :class="rowError(idx) ? 'border-red-500 bg-red-50 dark:bg-red-900/30' : ''"
                                       x-model.number="row.return_qty"
                                       :name="`lines[${idx}][quantity]`"
                                       @input="recalc()"
                                       placeholder="0.00">
                            </td>

                            <td class="px-4 py-3 text-right">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       class="form-input text-right w-28"
                                       x-model.number="row.unit_cost"
                                       :name="`lines[${idx}][unit_cost]`"
                                       @input="recalc()">
                            </td>

                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100"
                                x-text="'RWF ' + money((row.return_qty || 0) * (row.unit_cost || 0))"></td>
                        </tr>
                    </template>

                    <tr x-show="lines.length === 0">
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            No items available for return.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Notes + Summary --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">
                    Notes
                </label>
                <textarea name="notes"
                          rows="4"
                          class="form-textarea w-full"
                          x-model="form.notes"
                          placeholder="Optional notes about this return..."></textarea>
            </div>

            <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 space-y-3">
                <div class="flex justify-between items-center text-sm font-medium pb-2 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">Total Return Value:</span>
                    <span class="text-lg font-bold text-gray-900 dark:text-gray-100"
                          x-text="'RWF ' + money(total)"></span>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">
                        Refund Amount
                    </label>
                    <input type="number"
                           step="0.01"
                           min="0"
                           name="refund_amount"
                           class="form-input text-right w-full text-lg font-semibold @error('refund_amount','purchaseReturn') border-red-500 @enderror"
                           x-model.number="form.refund_amount"
                           @input="clampRefund()"
                           placeholder="0.00">
                    @error('refund_amount', 'purchaseReturn')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="text-xs text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-2 rounded border border-blue-200 dark:border-blue-800">
                    <strong>Note:</strong>
                    Refund amount cannot exceed total return value. Leave at 0 to create a credit note only.
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-between items-center gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="text-sm">
                <span x-show="!valid" class="text-red-600 font-medium">
                    ⚠️ Please enter valid return quantities before submitting
                </span>
                <span x-show="valid" class="text-green-600 font-medium">
                    ✓ Ready to submit
                </span>
            </div>
            <div class="flex gap-3">
                <button type="button"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-900 font-medium transition"
                        @click="closeModal()">
                    Cancel
                </button>

                <button type="submit"
                        class="px-6 py-2 rounded-lg font-medium transition"
                        :disabled="!valid"
                        :class="valid
                            ? 'bg-indigo-600 text-white hover:bg-indigo-700 cursor-pointer'
                            : 'bg-gray-300 text-gray-500 cursor-not-allowed'">
                    Confirm Return
                </button>
            </div>
        </div>
    </form>
</div>
