@extends('layouts.app')
@section('title', 'New Inter-Company Loan')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="plus-circle" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Record New Loan</span>
        </h1>
        <a href="{{ route('item-loans.index') }}" class="btn btn-outline">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('item-loans.store') }}" class="space-y-5 rounded-xl border dark:border-gray-700 p-6 bg-white dark:bg-gray-800">
        @csrf

        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium">Partner Company <span class="text-red-600">*</span></label>
                <button type="button" @click="$dispatch('open-partner-modal')" 
                        class="text-xs text-indigo-600 hover:text-indigo-500 font-medium flex items-center gap-1">
                    <i data-lucide="plus" class="w-3 h-3"></i> New Partner
                </button>
            </div>
            <select name="partner_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                <option value="">Select partner...</option>
                @foreach ($partners as $p)
                    <option value="{{ $p->id }}" @selected(old('partner_id') == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Direction <span class="text-red-600">*</span></label>
                <select name="direction" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="given" @selected(old('direction')==='given')>We LENT to them (given)</option>
                    <option value="taken" @selected(old('direction')==='taken')>We BORROWED from them (taken)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Unit <span class="text-xs text-gray-500">(optional)</span></label>
                <input type="text" name="unit" value="{{ old('unit') }}" placeholder="pcs, bottles, boxes"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" maxlength="20">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Linked Product <span class="text-xs text-gray-500">(Inventory Tracking)</span></label>
                <select name="product_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="">-- No Link (Manual Item) --</option>
                    @foreach ($products as $prod)
                        <option value="{{ $prod->id }}" @selected(old('product_id') == $prod->id)>
                            {{ $prod->name }} (Stock: {{ $prod->quantity }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Item Name <span class="text-red-600">*</span></label>
                <input type="text" name="item_name" value="{{ old('item_name') }}" required
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" maxlength="255" placeholder="e.g., Plastic Chair">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Quantity <span class="text-red-600">*</span></label>
                <input type="number" step="0.01" min="0.01" name="quantity" value="{{ old('quantity') }}" required
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Loan Date <span class="text-red-600">*</span></label>
                <input type="date" name="loan_date" value="{{ old('loan_date', now()->toDateString()) }}" required
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Due Date <span class="text-xs text-gray-500">(optional)</span></label>
                <input type="date" name="due_date" value="{{ old('due_date') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Notes</label>
            <textarea name="notes" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Any additional details...">{{ old('notes') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('item-loans.index') }}" class="btn btn-outline">Cancel</a>
            <button class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

{{-- Create Partner Modal --}}
{{-- Create Partner Modal (Alpine) --}}
<div x-data="{ open: false }"
     @open-partner-modal.window="open = true"
     @close-partner-modal.window="open = false"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     x-transition>
    
    <div @click.outside="open = false"
         class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
        
        {{-- Header --}}
        <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 border-b dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-bold text-lg flex items-center gap-2 text-gray-800 dark:text-gray-100">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg text-indigo-600 dark:text-indigo-400">
                    <i data-lucide="building-2" class="w-5 h-5"></i>
                </div>
                Add New Partner
            </h3>
            <button @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- Body --}}
        <form id="createPartnerForm" class="p-6 space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">
                    Company Name <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="briefcase" class="w-4 h-4"></i>
                    </div>
                    <input type="text" name="name" required 
                           class="input input-bordered w-full pl-10 bg-white dark:bg-gray-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Contact Person</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </div>
                        <input type="text" name="contact_person" 
                               class="input input-bordered w-full pl-10 bg-white dark:bg-gray-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Phone</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="phone" class="w-4 h-4"></i>
                        </div>
                        <input type="text" name="phone" 
                               class="input input-bordered w-full pl-10 bg-white dark:bg-gray-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-3 mt-8 pt-2">
                <button type="button" class="btn btn-ghost text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800" 
                        @click="open = false">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary px-6" id="savePartnerBtn">
                    <span class="flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        Save Partner
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('createPartnerForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('savePartnerBtn');
        const originalText = btn.innerText;
        btn.innerText = 'Saving...';
        btn.disabled = true;

        // Clear previous errors
        document.querySelectorAll('.error-msg').forEach(el => el.remove());
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));

        try {
            const formData = new FormData(this);
            const response = await fetch("{{ route('partner-companies.store') }}", {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // Add to select and select it
                const select = document.querySelector('select[name="partner_id"]');
                const option = new Option(result.partner.name, result.partner.id, true, true);
                select.add(option);
                
                // Close modal and reset form
                window.dispatchEvent(new CustomEvent('close-partner-modal'));
                this.reset();
                
                // Optional: Show toast/alert
                // alert('Partner created!'); 
            } else {
                // Handle Validation Errors
                if (result.errors) {
                    for (const [key, messages] of Object.entries(result.errors)) {
                        const input = this.querySelector(`[name="${key}"]`);
                        if (input) {
                            input.classList.add('input-error'); // Add error class if using DaisyUI or similar
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'text-red-500 text-xs mt-1 error-msg';
                            errorDiv.innerText = messages[0];
                            input.parentNode.appendChild(errorDiv);
                        }
                    }
                } else {
                    alert(result.message || 'Failed to create partner');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while creating the partner.');
        } finally {
            btn.innerText = originalText;
            btn.disabled = false;
        }
    });
</script>
@endsection
