@extends('layouts.app')
@section('title', 'New Purchase')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    @can('purchases.create')
        {{-- Header --}}
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="package-plus" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                <span>New Purchase</span>
            </h1>
            <a href="{{ route('purchases.index') }}" class="btn btn-secondary flex items-center gap-1 text-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
        </div>

        {{-- Form --}}
        <form action="{{ route('purchases.store') }}" method="POST" class="space-y-6">
            @csrf
            @include('purchases._form', [
                'suppliers' => $suppliers,
                'products'  => $products,
            ])
        </form>
    @else
        {{-- No permission message --}}
        <div class="rounded-xl border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-6 space-y-2">
            <div class="flex items-center gap-2 text-amber-800 dark:text-amber-200">
                <i data-lucide="lock" class="w-5 h-5"></i>
                <h1 class="text-lg font-semibold">Permission required</h1>
            </div>
            <p class="text-sm text-amber-800 dark:text-amber-100">
                You don’t have permission to create purchases. Please contact your administrator if you think this is a mistake.
            </p>
            <div class="mt-3">
                <a href="{{ route('purchases.index') }}" class="btn btn-secondary btn-sm inline-flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Purchases
                </a>
            </div>
        </div>
    @endcan

</div>
@endsection
