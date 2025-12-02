@extends('layouts.app')
@section('title', 'Partner Companies')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="users" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Partner Companies</span>
        </h1>

        <div class="flex items-center gap-2">
            <a href="{{ route('item-loans.index') }}" class="btn btn-outline">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Loans
            </a>
            {{-- We can reuse the modal here or just rely on the create page. For now, let's keep it simple --}}
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="rounded-xl border dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
        <div class="flex gap-3">
            <div class="flex-1">
                <label class="block text-xs text-gray-500">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, contact, phone..."
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div class="flex items-end">
                <button class="btn btn-primary">Search</button>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-auto rounded-xl border dark:border-gray-700 bg-white dark:bg-gray-800">
        <table class="min-w-full text-sm">
            <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left">Company Name</th>
                    <th class="px-4 py-3 text-left">Contact Person</th>
                    <th class="px-4 py-3 text-left">Phone</th>
                    <th class="px-4 py-3 text-center">Active Loans</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-gray-700">
            @forelse ($partners as $partner)
                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-900/40">
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $partner->name }}</td>
                    <td class="px-4 py-3">{{ $partner->contact_person ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $partner->phone ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($partner->item_loans_count > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                                {{ $partner->item_loans_count }}
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right flex justify-end gap-2">
                        <a href="{{ route('partner-companies.edit', $partner) }}" class="btn btn-sm btn-ghost text-indigo-600">
                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                        </a>
                        
                        @if($partner->item_loans_count === 0)
                            <form action="{{ route('partner-companies.destroy', $partner) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="button" 
                                        onclick="return false;"
                                        @click="$store.confirm.openWith($el.closest('form'))"
                                        class="btn btn-sm btn-ghost text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        @else
                            <button disabled class="btn btn-sm btn-ghost text-gray-400 cursor-not-allowed" title="Cannot delete partner with loans">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">No partners found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div>
        {{ $partners->links() }}
    </div>
</div>

{{-- Global Delete Confirmation Modal (Alpine Store) --}}
<div x-data
     x-show="$store.confirm.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     @keydown.escape.window="$store.confirm.close()"
     x-transition>
    <div @click.outside="$store.confirm.close()"
         class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Confirm Deletion</h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">
            Are you sure you want to delete this partner company? This action cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-outline" @click="$store.confirm.close()">Cancel</button>
            <button type="button" class="btn btn-danger" @click="$store.confirm.confirm()">
                Delete
            </button>
        </div>
    </div>
</div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('confirm', {
            open: false,
            submitEl: null,
            openWith(form) {
                this.submitEl = form;
                this.open = true;
            },
            close() {
                this.open = false;
                this.submitEl = null;
            },
            confirm() {
                if (this.submitEl) this.submitEl.submit();
                this.close();
            },
        });
    });
</script>
@endpush
@endsection
