<?php

namespace App\Http\Requests;

use App\Models\ExtraClassOffering;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExtraClassOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'extra_class_catalog_id' => ['required', 'exists:extra_class_catalogs,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'fee_category_id' => ['nullable', 'exists:fee_categories,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'fee_amount' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'remarks' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(ExtraClassOffering::statusOptions()))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $exists = \App\Models\ExtraClassOffering::query()
                ->where('extra_class_catalog_id', $this->input('extra_class_catalog_id'))
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('academic_year_id', 'This extra class already has an offering for the selected academic year.');
            }
        });
    }
}
