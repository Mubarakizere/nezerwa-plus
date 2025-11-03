@php
    $roleColors = [
        'admin'   => 'bg-indigo-600',
        'manager' => 'bg-green-600',
        'cashier' => 'bg-yellow-500',
    ];

    $titles = [
        'admin'   => 'Business Dashboard',
        'manager' => 'Management Dashboard',
        'cashier' => 'Daily Sales Dashboard',
    ];

    $color = $roleColors[$role] ?? 'bg-gray-600';
    $title = $titles[$role] ?? 'Dashboard';
@endphp

{{-- DASHBOARD HEADER --}}
<div class="{{ $color }} text-white px-5 py-4 rounded-lg shadow-sm
            flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3
            dark:shadow-none transition-all duration-300">

    <div class="flex items-center space-x-2">
        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
        <h2 class="font-semibold text-lg sm:text-xl leading-tight">
            {{ $title }}
        </h2>
    </div>

    <span class="text-sm sm:text-base opacity-90">
        Updated {{ now()->format('d M Y H:i') }}
    </span>
</div>

{{-- QUICK ACTION BUTTONS --}}
<div class="mt-5 flex flex-wrap items-center gap-3">

    @if(in_array($role, ['admin', 'manager']))
        <a href="{{ route('sales.create') }}"
           class="btn btn-primary flex items-center justify-center gap-1.5 text-sm sm:text-base px-3 sm:px-4 py-2 sm:py-2.5">
            <i data-lucide="shopping-cart" class="w-4 h-4"></i>
            <span>New Sale</span>
        </a>

        <a href="{{ route('purchases.create') }}"
           class="btn btn-secondary flex items-center justify-center gap-1.5 text-sm sm:text-base px-3 sm:px-4 py-2 sm:py-2.5">
            <i data-lucide="package-plus" class="w-4 h-4"></i>
            <span>New Purchase</span>
        </a>

        <a href="{{ route('loans.create') }}"
           class="btn btn-success flex items-center justify-center gap-1.5 text-sm sm:text-base px-3 sm:px-4 py-2 sm:py-2.5">
            <i data-lucide="hand-coins" class="w-4 h-4"></i>
            <span>Add Loan</span>
        </a>

        {{-- <a href="{{ route('transactions.create') }}"
           class="btn btn-outline flex items-center justify-center gap-1.5 text-sm sm:text-base px-3 sm:px-4 py-2 sm:py-2.5">
            <i data-lucide="activity" class="w-4 h-4"></i>
            <span>Add Transaction</span>
        </a> --}}

    @elseif($role === 'cashier')
        <a href="{{ route('sales.create') }}"
           class="btn btn-primary flex items-center justify-center gap-1.5 text-sm sm:text-base px-3 sm:px-4 py-2 sm:py-2.5">
            <i data-lucide="shopping-cart" class="w-4 h-4"></i>
            <span>New Sale</span>
        </a>
    @endif
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
