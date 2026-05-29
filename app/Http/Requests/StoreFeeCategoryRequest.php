<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeeCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:fee_categories,name'],
            'code' => ['required', 'string', 'max:50', 'unique:fee_categories,code'],
            'type' => ['required', 'in:mandatory,optional'],
            'allow_discount' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'allow_discount' => $this->boolean('allow_discount'),
        ]);
    }
}
