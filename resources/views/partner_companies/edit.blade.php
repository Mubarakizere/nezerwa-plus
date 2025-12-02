@extends('layouts.app')
@section('title', 'Edit Partner Company')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Edit Partner Company</h1>
        <a href="{{ route('partner-companies.index') }}" class="btn btn-outline">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    <form method="POST" action="{{ route('partner-companies.update', $partner) }}" class="space-y-5 rounded-xl border dark:border-gray-700 p-6 bg-white dark:bg-gray-800">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium mb-1">Company Name <span class="text-red-600">*</span></label>
            <input type="text" name="name" value="{{ old('name', $partner->name) }}" required 
                   class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Contact Person</label>
                <input type="text" name="contact_person" value="{{ old('contact_person', $partner->contact_person) }}" 
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @error('contact_person') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $partner->phone) }}" 
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $partner->email) }}" 
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Address</label>
                <input type="text" name="address" value="{{ old('address', $partner->address) }}" 
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @error('address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Notes</label>
            <textarea name="notes" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('notes', $partner->notes) }}</textarea>
            @error('notes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t dark:border-gray-700">
            <a href="{{ route('partner-companies.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary">Update Partner</button>
        </div>
    </form>
</div>
@endsection
