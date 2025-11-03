<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Always authorize since this form is protected by auth middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc,dns',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],

            // ✅ Photo — allow 30MB, only common formats
            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:30720', // 30 MB (in KB)
            ],
        ];
    }

    /**
     * Custom validation messages (optional but cleaner UX)
     */
    public function messages(): array
    {
        return [
            'photo.max' => 'The profile photo must not exceed 30 MB.',
            'photo.mimes' => 'Only JPG, JPEG, PNG, or WEBP images are allowed.',
        ];
    }
}
