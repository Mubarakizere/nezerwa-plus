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
