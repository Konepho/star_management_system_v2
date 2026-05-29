<?php

namespace App\Http\Requests;

use App\Models\DiscountDefinition;
use App\Models\StudentDiscount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStudentDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'discount_definition_id' => ['required', 'exists:discount_definitions,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(array_keys(StudentDiscount::statusOptions()))],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $discountDefinition = DiscountDefinition::query()->find($this->input('discount_definition_id'));

            if ($discountDefinition && $discountDefinition->status !== 'active') {
                $validator->errors()->add('discount_definition_id', 'Only active discount definitions can be assigned to students.');
            }
        });
    }
}
