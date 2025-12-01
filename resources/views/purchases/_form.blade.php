@php
    $isEdit = isset($purchase);

    // Guard against divide-by-zero
    $taxPct = $discountPct = 0;
    if ($isEdit && ($purchase->subtotal ?? 0) > 0) {
        $taxPct      = round((($purchase->tax ?? 0) / $purchase->subtotal) * 100, 2);
        $discountPct = round((($purchase->discount ?? 0) / $purchase->subtotal) * 100, 2);
    }

    // Prepare initial state for Alpine
    $initialItems = old('products', []);
    if (empty($initialItems) && $isEdit) {
        $initialItems = $purchase->items->map(function($i) {
            return [
                'product_id' => $i->product_id,
                'quantity' => $i->quantity,
                'unit_cost' => $i->unit_cost,
            ];
        });
    }
    // Ensure at least one empty line
    if (empty($initialItems)) {
        $initialItems = [['product_id' => '', 'quantity' => 1, 'unit_cost' => 0]];
    }

    $status = old('status', $isEdit ? ($purchase->status ?? 'received') : 'received');
    $amountPaid = old('amount_paid', $isEdit ? ($purchase->amount_paid ?? 0) : 0);
    $paymentChannel = old('payment_channel', $isEdit ? ($purchase->payment_channel ?? 'cash') : 'cash');
@endphp

<div x-data="purchaseForm()" x-init="init()">
    {{-- Error Display --}}
    @if($errors->any())
        <div class="mb-6 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 p-4 rounded-r-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i data-lucide="alert-circle" class="h-5 w-5 text-red-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                        There were errors with your submission
                    </h3>
                    <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ isset($purchase) ? route('purchases.update', $purchase) : route('purchases.store') }}"
          method="POST"
          @submit.prevent="submitForm">
        @csrf
        @if(isset($purchase))
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Supplier & Products --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Supplier Selection --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i data-lucide="truck" class="w-5 h-5 text-indigo-500"></i>
                        Supplier Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Supplier <span class="text-red-500">*</span></label>
                            <select name="supplier_id" class="form-select w-full" required>
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" @selected(isset($purchase) && $purchase->supplier_id == $s->id)>
                                        {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Purchase Date</label>
                            <input type="date" name="purchase_date"
                                   value="{{ old('purchase_date', isset($purchase) ? $purchase->purchase_date->format('Y-m-d') : date('Y-m-d')) }}"
                                   class="form-input w-full">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Reference (Invoice #)</label>
                            <input type="text" name="reference_number"
                                   value="{{ old('reference_number', $purchase->reference_number ?? '') }}"
                                   class="form-input w-full"
                                   placeholder="e.g. INV-2023-001">
                        </div>
                    </div>
                </div>

                {{-- Products Table --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="shopping-cart" class="w-5 h-5 text-indigo-500"></i>
                            Items
                        </h2>
                        <button type="button" @click="addItem()" class="btn btn-secondary text-sm flex items-center gap-1">
                            <i data-lucide="plus" class="w-4 h-4"></i> Add Item
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-2 w-10">#</th>
                                    <th class="py-2 min-w-[250px]">Product</th>
                                    <th class="py-2 w-24">Qty</th>
                                    <th class="py-2 w-32">Unit Cost</th>
                                    <th class="py-2 w-32 text-right">Total</th>
                                    <th class="py-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <template x-for="(row, idx) in items" :key="row.key">
                                    <tr>
                                        <td class="py-3 text-gray-500 text-sm" x-text="idx + 1"></td>
                                        <td class="py-3 pr-4">
                                            <x-product-search
                                                ::name="`products[${idx}][product_id]`"
                                                ::value="row.product_id"
                                                @product-selected="onProductSelected($event.detail, idx)"
                                                @product-cleared="onProductCleared(idx)"
                                                required="true"
                                            />
                                        </td>
                                        <td class="py-3 pr-4">
                                            <input type="number" :name="`products[${idx}][quantity]`"
                                                   x-model.number="row.quantity"
                                                   min="1"
                                                   class="form-input w-full text-center"
                                                   required>
                                        </td>
                                        <td class="py-3 pr-4">
                                            <input type="number" :name="`products[${idx}][unit_cost]`"
                                                   x-model.number="row.unit_cost"
                                                   step="0.01"
                                                   class="form-input w-full text-right"
                                                   required>
                                        </td>
                                        <td class="py-3 text-right font-medium text-gray-900 dark:text-gray-100">
                                            <span x-text="formatMoney(row.quantity * row.unit_cost)"></span>
                                        </td>
                                        <td class="py-3 text-right">
                                            <button type="button" @click="removeItem(idx)" class="text-red-500 hover:text-red-700 p-1">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="py-4 text-right font-bold text-gray-700 dark:text-gray-300">Grand Total:</td>
                                    <td class="py-4 text-right font-bold text-xl text-indigo-600 dark:text-indigo-400">
                                        <span x-text="formatMoney(grandTotal)"></span>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Right Column: Payment & Notes --}}
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i data-lucide="credit-card" class="w-5 h-5 text-indigo-500"></i>
                        Payment & Status
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select w-full" x-model="status">
                                <option value="received">Received</option>
                                <option value="pending">Pending</option>
                                <option value="ordered">Ordered</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Amount Paid</label>
                            <div class="flex gap-2">
                                <input type="number" name="amount_paid"
                                       x-model.number="amountPaid"
                                       step="0.01"
                                       class="form-input w-full">
                                <button type="button" @click="payFull()" class="btn btn-outline px-3">Full</button>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Payment Channel</label>
                            <div class="flex flex-wrap gap-2 mt-1">
                                <template x-for="channel in ['cash', 'bank', 'momo', 'mobile']">
                                    <button type="button"
                                            @click="paymentChannel = channel"
                                            :class="paymentChannel === channel
                                                ? 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 dark:border-indigo-700'
                                                : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700'"
                                            class="px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors capitalize">
                                        <span x-text="channel"></span>
                                    </button>
                                </template>
                                <input type="hidden" name="payment_channel" x-model="paymentChannel">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-500">Total Payable:</span>
                                <span class="font-medium" x-text="formatMoney(grandTotal)"></span>
                            </div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-500">Paid:</span>
                                <span class="font-medium text-emerald-600" x-text="formatMoney(amountPaid)"></span>
                            </div>
                            <div class="flex justify-between text-sm font-bold">
                                <span class="text-gray-700 dark:text-gray-300">Balance Due:</span>
                                <span :class="balanceDue > 0 ? 'text-red-600' : 'text-gray-500'"
                                      x-text="formatMoney(balanceDue)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="3" class="form-textarea w-full mt-1">{{ old('notes', $purchase->notes ?? '') }}</textarea>
                </div>

                <button type="submit"
                        class="w-full btn btn-primary py-3 text-base flex justify-center items-center gap-2 shadow-lg shadow-indigo-500/20">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    {{ isset($purchase) ? 'Update Purchase' : 'Create Purchase' }}
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function purchaseForm() {
        const rid = () => (crypto.randomUUID?.() || String(Date.now() + Math.random()));

        return {
            items: (() => {
                const data = @json($initialItems);
                return data.map(i => ({
                    key: rid(),
                    product_id: i.product_id,
                    quantity: i.quantity,
                    unit_cost: i.unit_cost
                }));
            })(),

            status: @json($status),
            amountPaid: @json($amountPaid),
            paymentChannel: @json($paymentChannel),

            init() {
                //
            },

            addItem() {
                this.items.push({ key: rid(), product_id: '', quantity: 1, unit_cost: 0 });
            },

            removeItem(index) {
                if (this.items.length > 1) {
                    this.items.splice(index, 1);
                } else {
                    this.items[0] = { key: rid(), product_id: '', quantity: 1, unit_cost: 0 };
                }
            },

            onProductSelected(product, index) {
                if (!product) return;
                this.items[index].product_id = product.id;
                this.items[index].unit_cost = parseFloat(product.cost_price || 0);
            },

            onProductCleared(index) {
                this.items[index].product_id = '';
                this.items[index].unit_cost = 0;
            },

            get grandTotal() {
                return this.items.reduce((sum, item) => {
                    return sum + (item.quantity * item.unit_cost);
                }, 0);
            },

            get balanceDue() {
                return Math.max(0, this.grandTotal - this.amountPaid);
            },

            payFull() {
                this.amountPaid = this.grandTotal;
            },

            formatMoney(amount) {
                return 'RWF ' + Number(amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            },

            submitForm(e) {
                e.target.submit();
            }
        }
    }
</script>
@endpush
