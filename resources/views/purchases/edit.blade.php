@extends('layouts.app')
@section('title', "Edit Purchase #{$purchase->id}")

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="file-edit" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <span>Edit Purchase #{{ $purchase->id }}</span>
        </h1>
        <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-secondary flex items-center gap-1 text-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    <form method="POST" action="{{ route('purchases.update', $purchase) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('purchases._form', ['suppliers'=>$suppliers, 'products'=>$products, 'purchase'=>$purchase])
    </form>
</div>
@endsection
