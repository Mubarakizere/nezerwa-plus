<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
        $this->merge([
            'is_active'  => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }

    public function rules(): array
{
    $id = $this->route('category')?->id;

    return [
        'name'        => [
            'required','string','max:255',
            \Illuminate\Validation\Rule::unique('categories', 'name')
                ->ignore($id)
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

    protected function failedValidation(Validator $validator)
    {
        $rid = $this->rid();
        Log::warning('CategoryUpdateRequest.validation_failed', [
            'rid'    => $rid,
            'category_id' => $this->route('category')?->id,
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
