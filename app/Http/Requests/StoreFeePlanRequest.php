<?php

namespace App\Http\Requests;

use App\Models\FeePlan;
use App\Models\Grade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFeePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:fee_plans,code'],
            'grade_group' => ['nullable', Rule::in(array_keys(Grade::groupOptions()))],
            'status' => ['required', Rule::in(array_keys(FeePlan::statusOptions()))],
            'description' => ['nullable', 'string'],
            'fee_structure_ids' => ['required', 'array', 'min:1'],
            'fee_structure_ids.*' => ['integer', 'exists:fee_structures,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $academicYearId = (int) $this->input('academic_year_id');
            $feeStructureIds = collect($this->input('fee_structure_ids', []))->map(fn ($id) => (int) $id);

            if ($feeStructureIds->isEmpty()) {
                return;
            }

            $invalidCount = \App\Models\FeeStructure::query()
                ->whereIn('id', $feeStructureIds)
                ->where('academic_year_id', '!=', $academicYearId)
                ->count();

            if ($invalidCount > 0) {
                $validator->errors()->add('fee_structure_ids', 'All fee structures in the plan must belong to the selected academic year.');
            }
        });
    }
}
