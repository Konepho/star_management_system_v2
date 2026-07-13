<?php

namespace App\Http\Requests;

use App\Models\StudentExtraClassRegistration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStudentExtraClassRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'extra_class_offering_id' => ['required', 'exists:extra_class_offerings,id'],
            'registration_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(StudentExtraClassRegistration::statusOptions()))],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var StudentExtraClassRegistration $registration */
            $registration = $this->route('student_extra_class_registration');
            $offering = \App\Models\ExtraClassOffering::query()->find($this->input('extra_class_offering_id'));

            if (! $offering) {
                return;
            }

            if ((int) $offering->academic_year_id !== (int) $this->input('academic_year_id')) {
                $validator->errors()->add('extra_class_offering_id', 'The selected offering must belong to the selected academic year.');
            }

            $exists = \App\Models\StudentExtraClassRegistration::query()
                ->where('student_id', $this->input('student_id'))
                ->where('extra_class_offering_id', $this->input('extra_class_offering_id'))
                ->where('id', '!=', $registration->id)
                ->exists();

            if ($exists) {
                $validator->errors()->add('extra_class_offering_id', 'This student is already registered for the selected extra class offering.');
            }
        });
    }
}
