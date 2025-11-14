<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategoryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gate via middleware/policies if needed
    }

    /** Generate/return a request id so we can trace in logs & flash */
    protected function rid(): string
    {
        $rid = $this->attributes->get('rid');
        if (!$rid) {
            $rid = (string) Str::uuid();
            $this->attributes->set('rid', $rid);
        }
        return $rid;
    }

    protected function prepareForValidation(): void
    {
        // Coerce booleans & defaults so rules see correct types
        $this->merge([
            'is_active'  => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }

    public function rules(): array
{
    return [
        'name'        => [
            'required','string','max:255',
            \Illuminate\Validation\Rule::unique('categories', 'name')
                ->where(fn($q) => $q
                    ->where('kind', $this->input('kind', 'both'))
                    ->whereNull('deleted_at') // ← ignore soft-deleted
                ),
        ],
        'description' => ['nullable','string'],
        'kind'        => ['required','in:product,expense,both'],
        'parent_id'   => ['nullable','integer','exists:categories,id'],
        'code'        => ['nullable','string','max:40'],
        'color'       => ['nullable','string','max:20'],
        'icon'        => ['nullable','string','max:50'],
        'is_active'   => ['boolean'],
        'sort_order'  => ['integer','min:0'],
    ];
}


    public function messages(): array
    {
        return [
            'name.unique' => 'A category with this name already exists for the selected kind.',
        ];
    }

    protected function failedAuthorization()
    {
        $rid = $this->rid();
        Log::warning('CategoryStoreRequest.auth_failed', [
            'rid' => $rid,
            'user_id' => optional($this->user())->id,
        ]);
        parent::failedAuthorization(); // will throw
    }

    protected function failedValidation(Validator $validator)
    {
        $rid = $this->rid();
        Log::warning('CategoryStoreRequest.validation_failed', [
            'rid'    => $rid,
            'input'  => collect($this->all())->except(['_token','_method'])->toArray(),
            'errors' => $validator->errors()->toArray(),
        ]);

        throw new HttpResponseException(
            back()->withInput()
                 ->withErrors($validator)
                 ->with('error', "Validation failed. Ref: {$rid}")
        );
    }
}
