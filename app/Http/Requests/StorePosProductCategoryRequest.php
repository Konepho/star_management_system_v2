<?php

namespace App\Http\Requests;

use App\Models\PosProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', 'unique:pos_product_categories,code'],
            'description' => ['nullable', 'string'],
            'pos_section' => ['required', Rule::in(array_keys(PosProductCategory::sectionOptions()))],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
