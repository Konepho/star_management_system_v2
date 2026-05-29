<?php

namespace App\Http\Requests;

use App\Models\DiscountDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiscountDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:discount_definitions,name'],
            'code' => ['required', 'string', 'max:50', 'unique:discount_definitions,code'],
            'discount_type' => ['required', Rule::in(array_keys(DiscountDefinition::typeOptions()))],
            'value' => ['required', 'numeric', 'min:0.01'],
            'status' => ['required', Rule::in(array_keys(DiscountDefinition::statusOptions()))],
            'description' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('discount_type') === DiscountDefinition::TYPE_PERCENTAGE && (float) $this->input('value') > 100) {
                $validator->errors()->add('value', 'Percentage discounts cannot be greater than 100.');
            }
        });
    }
}
