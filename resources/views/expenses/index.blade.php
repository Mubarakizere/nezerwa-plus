@extends('layouts.app')
@section('title', 'Expenses')

@section('content')
<style>[x-cloak]{display:none!important}</style>

@php
    use Illuminate\Support\Str;

    $fmt     = fn($n) => number_format((float)$n, 2);
    $methods = ['cash','bank','momo'];

    // Current page collection
    $rows = $expenses->getCollection();

    $pageTotal = $rows->sum(fn($e) => (float)($e->amount ?? 0));

    // Per-method summary for current page
    $byMethod = collect($methods)->mapWithKeys(function ($m) use ($rows) {
        $filtered = $rows->filter(fn($e) => strtolower($e->method ?? '') === $m);
        return [$m => [
            'count'  => $filtered->count(),
            'amount' => (float) $filtered->sum('amount'),
        ]];
    });
@endphp

<div
    x-data="{
        showDel:false, delAction:'', delName:'',
        openDel(action, name){ this.delAction = action; this.delName = name; this.showDel = true },
        closeDel(){ this.showDel=false; this.delAction=''; this.delName='' }
    }"
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="wallet" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                <span>Expenses</span>
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Record and review operating, direct, and other expenses.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('expenses.create')
                <a href="{{ route('expenses.create') }}" class="btn btn-primary flex items-center gap-1">
                    <i data-lucide="plus" class="w-4 h-4"></i> New Expense
                </a>
            @endcan
        </div>
    </div>

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('expenses.index') }}"
          class="rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-4">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Category</label>
                <select name="category_id" class="form-select w-full">
                    <option value="">All</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected((string)request('category_id')===(string)$c->id)>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Supplier</label>
                <select name="supplier_id" class="form-select w-full">
                    <option value="">All</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected((string)request('supplier_id')===(string)$s->id)>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Method</label>
                <select name="method" class="form-select w-full">
                    <option value="">Any</option>
                    @foreach($methods as $m)
                        <option value="{{ $m }}" @selected(request('method')===$m)>{{ strtoupper($m) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-input w-full">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-input w-full">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Search</label>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Ref, note, category, supplier…"
                       class="form-input w-full">
            </div>
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
            <button class="btn btn-secondary flex items-center gap-1">
                <i data-lucide="filter" class="w-4 h-4"></i> Apply
            </button>
            <a href="{{ route('expenses.index') }}" class="btn btn-outline flex items-center gap-1">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Reset
            </a>
        </div>
    </form>

    {{-- PAGE SUMMARY --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
            <div class="text-xs text-gray-500 dark:text-gray-400">Page Total</div>
            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                RWF {{ $fmt($pageTotal) }}
            </div>
            <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                {{ $rows->count() }} expense(s) on this page
            </div>
        </div>

        @php
            $mStyles = [
                'cash' => ['label' => 'Cash', 'clr' => 'green'],
                'bank' => ['label' => 'Bank', 'clr' => 'blue'],
                'momo' => ['label' => 'MoMo', 'clr' => 'purple'],
            ];
        @endphp

        @foreach($byMethod as $key => $agg)
            @php $s = $mStyles[$key]; @endphp
            <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $s['label'] }} ({{ $agg['count'] }})
                    </span>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold bg-{{ $s['clr'] }}-100 text-{{ $s['clr'] }}-800 dark:bg-{{ $s['clr'] }}-900/40 dark:text-{{ $s['clr'] }}-300">
                        {{ strtoupper($key) }}
                    </span>
                </div>
                <div class="mt-2 text-sm">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Amount</div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">
                        RWF {{ $fmt($agg['amount']) }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- TABLE --}}
    <div class="rounded-2xl overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
        <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center gap-2">
            <i data-lucide="list" class="w-4 h-4 text-indigo-500"></i>
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Results</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                        <th class="px-5 py-3">#</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3">Supplier</th>
                        <th class="px-5 py-3">Method</th>
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                        <th class="px-5 py-3">Note</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                    @forelse($expenses as $e)
                        @php
                            $method = strtolower($e->method ?? '');
                            $methodClass = [
                                'cash' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                'bank' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                'momo' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                            ][$method] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-300';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                #{{ $e->id }}
                            </td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ optional($e->date)->format('Y-m-d') ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-900 dark:text-gray-100">
                                {{ $e->category->name ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-900 dark:text-gray-100">
                                {{ $e->supplier->name ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $methodClass }}">
                                    {{ strtoupper($e->method ?? '—') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                {{ $e->reference ?: '—' }}
                            </td>
                            <td class="px-5 py-3 text-right font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                RWF {{ $fmt($e->amount) }}
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                <span class="line-clamp-2">
                                    {{ Str::limit($e->note ?? '—', 80) }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1.5 flex-wrap">
                                    @can('expenses.view')
                                        <a href="{{ route('expenses.show', $e) }}" class="btn btn-outline text-xs flex items-center gap-1">
                                            <i data-lucide="eye" class="w-4 h-4"></i> View
                                        </a>
                                    @endcan

                                    @can('expenses.edit')
                                        <a href="{{ route('expenses.edit', $e) }}" class="btn btn-secondary text-xs flex items-center gap-1">
                                            <i data-lucide="file-edit" class="w-4 h-4"></i> Edit
                                        </a>
                                    @endcan

                                    @can('expenses.delete')
                                        <form x-on:submit.prevent="openDel($el.action, 'Expense #{{ $e->id }}')"
                                              method="POST"
                                              action="{{ route('expenses.destroy', $e) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger text-xs flex items-center gap-1">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i> Delete
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-6 text-center text-gray-500 dark:text-gray-400">
                                No expenses found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot class="bg-gray-50 dark:bg-gray-800/50 text-sm">
                    <tr>
                        <td colspan="6" class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">
                            Page Total
                        </td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                            RWF {{ $fmt($pageTotal) }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-800">
            {{ $expenses->withQueryString()->links() }}
        </div>
    </div>

    {{-- DELETE MODAL --}}
    <div x-cloak x-show="showDel" class="fixed inset-0 z-40">
        <div x-show="showDel" x-transition.opacity class="absolute inset-0 bg-black/40"></div>
        <div x-show="showDel" x-transition class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-md rounded-xl bg-white dark:bg-gray-900 ring-1 ring-gray-200 dark:ring-gray-800 shadow-xl">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600"></i>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Delete Expense</h3>
                </div>
                <div class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                    Are you sure you want to delete
                    <span class="font-semibold" x-text="delName"></span>?
                    This action cannot be undone.
                </div>
                <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2">
                    <button type="button" @click="closeDel()" class="btn btn-outline">Cancel</button>
                    <form :action="delAction" method="POST">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush
@endsection
