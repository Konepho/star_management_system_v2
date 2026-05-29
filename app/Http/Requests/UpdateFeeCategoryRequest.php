<?php

namespace App\Http\Requests;

use App\Models\FeeCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeeCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var FeeCategory $feeCategory */
        $feeCategory = $this->route('fee_category');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('fee_categories', 'name')->ignore($feeCategory->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('fee_categories', 'code')->ignore($feeCategory->id)],
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
