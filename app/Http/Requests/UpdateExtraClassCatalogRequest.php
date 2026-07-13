<?php

namespace App\Http\Requests;

use App\Models\ExtraClassCatalog;
use App\Models\Grade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExtraClassCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ExtraClassCatalog $extraClassCatalog */
        $extraClassCatalog = $this->route('extra_class_catalog');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('extra_class_catalogs', 'code')->ignore($extraClassCatalog->id)],
            'grade_group' => ['nullable', Rule::in(array_keys(Grade::groupOptions()))],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(ExtraClassCatalog::statusOptions()))],
        ];
    }
}
