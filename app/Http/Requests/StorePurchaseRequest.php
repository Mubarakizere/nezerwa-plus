<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route middleware handles role gating; this is fine.
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'   => ['required', 'exists:suppliers,id'],
            'purchased_at'  => ['required', 'date'], // e.g. 2025-11-06 or with time
            'reference_no'  => ['nullable', 'string', 'max:100'],

            // Payment
            'amount_paid'   => ['nullable', 'numeric', 'min:0'],
            'method'        => [
                // Only required when amount_paid is present / > 0
                'nullable',
                'required_with:amount_paid',
                Rule::in(['cash', 'bank', 'momo']),
            ],

            // Totals (purchase-level)
            'tax_rate'      => ['nullable', 'numeric', 'min:0', 'max:100'],   // percentage
            'discount'      => ['nullable', 'numeric', 'min:0'],             // flat amount
            'notes'         => ['nullable', 'string', 'max:1000'],

            // Line items
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.product_id'        => ['required', 'exists:products,id'],
            'items.*.quantity'          => ['required', 'integer', 'min:1'],
            'items.*.unit_cost'         => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'     => 'Please add at least one item.',
            'items.min'          => 'Please add at least one item.',
            'method.required_with' => 'Choose a payment method when recording a payment.',
        ];
    }
}
