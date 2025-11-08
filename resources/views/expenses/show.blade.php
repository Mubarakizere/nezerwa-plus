@extends('layouts.app')
@section('title', "Expense #{$expense->id}")

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    use Carbon\Carbon;
    $fmt = fn($n) => number_format((float)$n, 2);

    $method = strtolower($expense->method ?? '');
    $methodBadge = [
        'cash'  => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        'bank'  => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'momo'  => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
    ][$method] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
@endphp

<div
    x-data="{
        showDel:false, delAction:'', delName:'',
        openDel(action, name){ this.delAction = action; this.delName = name; this.showDel = true },
        closeDel(){ this.showDel=false; this.delAction=''; this.delName='' }
    }"
    class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-start gap-3">
            <i data-lucide="receipt" class="w-8 h-8 text-indigo-600 dark:text-indigo-400"></i>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    Expense #{{ $expense->id }}
                </h1>
                <div class="mt-1 flex flex-wrap items-center gap-2 text-sm">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full {{ $methodBadge }}">
                        <i data-lucide="wallet" class="w-3.5 h-3.5 mr-1"></i>{{ strtoupper($expense->method ?? '—') }}
                    </span>
                    <span class="text-gray-500 dark:text-gray-400">
                        on {{ optional($expense->date)->format('M j, Y') ?? Carbon::parse($expense->date)->format('M j, Y') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @role('admin|manager|accountant')
                <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-secondary flex items-center gap-1">
                    <i data-lucide="file-edit" class="w-4 h-4"></i> Edit
                </a>
                <a href="{{ route('expenses.index') }}" class="btn btn-outline flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                </a>
                <form x-on:submit.prevent="openDel($el.action, 'Expense #{{ $expense->id }}')" method="POST" action="{{ route('expenses.destroy', $expense) }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger flex items-center gap-1">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Delete
                    </button>
                </form>
            @endrole
        </div>
    </div>

    {{-- Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Amount card --}}
        <div class="lg:col-span-1">
            <div class="rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Amount</h3>
                    <i data-lucide="banknote" class="w-5 h-5 text-indigo-500"></i>
                </div>
                <div class="mt-3 text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                    RWF {{ $fmt($expense->amount) }}
                </div>
                @if($expense->reference)
                    <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                        Reference: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $expense->reference }}</span>
                    </div>
                @endif
                <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                    Created {{ optional($expense->created_at)->format('M j, Y H:i') }} by
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $expense->creator->name ?? '—' }}</span>
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Updated {{ optional($expense->updated_at)->diffForHumans() }}
                </div>
            </div>
        </div>

        {{-- Details --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-indigo-500"></i>
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Details</h2>
                </div>
                <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500 dark:text-gray-400">Category</div>
                        <div class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                            {{ $expense->category->name ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-500 dark:text-gray-400">Supplier</div>
                        <div class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                            {{ $expense->supplier->name ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-500 dark:text-gray-400">Date</div>
                        <div class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                            {{ optional($expense->date)->format('M j, Y') ?? Carbon::parse($expense->date)->format('M j, Y') }}
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-500 dark:text-gray-400">Method</div>
                        <div class="mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full {{ $methodBadge }}">
                                <i data-lucide="wallet" class="w-3.5 h-3.5 mr-1"></i>{{ strtoupper($expense->method ?? '—') }}
                            </span>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="text-gray-500 dark:text-gray-400">Note</div>
                        <div class="mt-1 font-medium text-gray-900 dark:text-gray-100 whitespace-pre-line">
                            {{ $expense->note ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete modal --}}
    <div x-cloak x-show="showDel" class="fixed inset-0 z-40">
        <div x-show="showDel" x-transition.opacity class="absolute inset-0 bg-black/40"></div>
        <div x-show="showDel" x-transition class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-md rounded-xl bg-white dark:bg-gray-900 ring-1 ring-gray-200 dark:ring-gray-800 shadow-xl">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600"></i>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Delete Expense</h3>
                </div>
                <div class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                    Are you sure you want to delete <span class="font-semibold" x-text="delName"></span>? This action cannot be undone.
                </div>
                <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2">
                    <button type="button" @click="closeDel()" class="btn btn-outline">Cancel</button>
                    <form :action="delAction" method="POST">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
