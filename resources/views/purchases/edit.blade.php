@extends('layouts.app')
@section('title', "Edit Purchase #{$purchase->id}")

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- 🔹 Page Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="file-edit" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <span>Edit Purchase #{{ $purchase->id }}</span>
        </h1>
        <a href="{{ route('purchases.index') }}" class="btn btn-secondary flex items-center gap-1 text-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    {{-- 🔹 Form --}}
    <form action="{{ route('purchases.update', $purchase->id) }}" method="POST"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-8">
        @csrf
        @method('PUT')

        {{-- Supplier & Date --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Supplier</label>
                <select name="supplier_id"
                        class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ $supplier->id == $purchase->supplier_id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Purchase Date</label>
                <input type="date" name="purchase_date" value="{{ $purchase->purchase_date }}"
                       class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                       required>
            </div>
        </div>

        {{-- Products --}}
        <div>
            <div class="flex justify-between items-center mb-2">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <i data-lucide="clipboard-list" class="w-4 h-4 text-indigo-500"></i> Products
                </h4>
                <button type="button" id="addRow"
                        class="btn btn-primary text-xs flex items-center gap-1">
                    <i data-lucide="plus" class="w-4 h-4"></i> Add Product
                </button>
            </div>

            <div id="product-rows" class="space-y-2">
                @foreach($purchase->items as $i => $item)
                    <div class="flex flex-wrap gap-2 bg-gray-50 dark:bg-gray-900/40 p-3 rounded-lg">
                        <select name="products[{{ $i }}][product_id]"
                                class="flex-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                required>
                            <option value="">-- Select Product --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ $product->id == $item->product_id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="number" name="products[{{ $i }}][quantity]"
                               value="{{ $item->quantity }}"
                               class="qty-field w-24 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Qty" required>
                        <input type="number" step="0.01" name="products[{{ $i }}][unit_cost]"
                               value="{{ $item->unit_cost }}"
                               class="cost-field w-32 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Unit Cost" required>
                        <input type="text"
                               class="subtotal-field w-32 border-gray-300 dark:border-gray-700 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-center"
                               value="{{ number_format($item->total_cost, 2) }}" readonly>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Totals --}}
        <div class="grid md:grid-cols-3 gap-6 border-t pt-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tax (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="tax" id="tax"
                       class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                       value="{{ $purchase->tax ?? 0 }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discount (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="discount" id="discount"
                       class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                       value="{{ $purchase->discount ?? 0 }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount Paid</label>
                <input type="number" step="0.01" name="amount_paid" id="amount_paid"
                       class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                       value="{{ $purchase->amount_paid ?? 0 }}">
            </div>
        </div>

        {{-- Payment Method --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Method</label>
            <select name="method"
                    class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                <option value="cash" {{ $purchase->method == 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="bank" {{ $purchase->method == 'bank' ? 'selected' : '' }}>Bank</option>
                <option value="momo" {{ $purchase->method == 'momo' ? 'selected' : '' }}>Mobile Money</option>
            </select>
        </div>

        {{-- Notes --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
            <textarea name="notes" rows="3"
                      class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">{{ $purchase->notes }}</textarea>
        </div>

        {{-- Total Display --}}
        <div class="bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100">Total Amount:</h3>
            <span id="total-display" class="text-2xl font-bold text-green-700 dark:text-green-400">
                {{ number_format($purchase->total_amount, 2) }}
            </span>
            <input type="hidden" id="total-amount" name="total_amount" value="{{ $purchase->total_amount }}">
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
            <button type="submit" class="btn btn-success flex items-center gap-1">
                <i data-lucide="save" class="w-4 h-4"></i> Update Purchase
            </button>
            <a href="{{ route('purchases.index') }}" class="btn btn-outline flex items-center gap-1">
                <i data-lucide="x" class="w-4 h-4"></i> Cancel
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();

    let rowCount = {{ count($purchase->items) }};
    const container = document.getElementById('product-rows');
    const totalDisplay = document.getElementById('total-display');
    const totalInput = document.getElementById('total-amount');

    // Add new product row
    document.getElementById('addRow').addEventListener('click', () => {
        const newRow = `
            <div class="flex flex-wrap gap-2 bg-gray-50 dark:bg-gray-900/40 p-3 rounded-lg mt-2">
                <select name="products[${rowCount}][product_id]"
                        class="flex-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" required>
                    <option value="">-- Select Product --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                <input type="number" name="products[${rowCount}][quantity]"
                       class="qty-field w-24 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Qty" required>
                <input type="number" step="0.01" name="products[${rowCount}][unit_cost]"
                       class="cost-field w-32 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Unit Cost" required>
                <input type="text" class="subtotal-field w-32 border-gray-300 dark:border-gray-700 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-center" value="0.00" readonly>
            </div>`;
        container.insertAdjacentHTML('beforeend', newRow);
        rowCount++;
    });

    // Auto totals
    document.addEventListener('input', () => {
        let total = 0;
        document.querySelectorAll('#product-rows > div').forEach(row => {
            const qty = parseFloat(row.querySelector('.qty-field')?.value || 0);
            const cost = parseFloat(row.querySelector('.cost-field')?.value || 0);
            const subtotal = qty * cost;
            row.querySelector('.subtotal-field').value = subtotal.toFixed(2);
            total += subtotal;
        });
        const tax = parseFloat(document.getElementById('tax').value || 0);
        const discount = parseFloat(document.getElementById('discount').value || 0);
        total = total + (total * tax / 100) - (total * discount / 100);
        totalDisplay.textContent = total.toFixed(2);
        totalInput.value = total.toFixed(2);
    });
});
</script>
@endpush
@endsection
