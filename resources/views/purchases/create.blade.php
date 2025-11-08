@extends('layouts.app')
@section('title', 'New Purchase')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="package-plus" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <span>New Purchase</span>
        </h1>
        <a href="{{ route('purchases.index') }}" class="btn btn-secondary flex items-center gap-1 text-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    <form action="{{ route('purchases.store') }}" method="POST" class="space-y-6">
        @csrf
        @include('purchases._form', ['suppliers'=>$suppliers, 'products'=>$products])
    </form>
</div>
@endsection
