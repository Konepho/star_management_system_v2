<?php

namespace App\Http\Requests;

use App\Models\ExtraClassCatalog;
use App\Models\Grade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExtraClassCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:extra_class_catalogs,code'],
            'grade_group' => ['nullable', Rule::in(array_keys(Grade::groupOptions()))],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(ExtraClassCatalog::statusOptions()))],
        ];
    }
}
