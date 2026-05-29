<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use App\Models\FeePlan;
use App\Models\Section;
use App\Support\SectionScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEnrollmentRequest extends FormRequest
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
            'grade_id' => ['required', 'exists:grades,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'fee_plan_id' => ['nullable', 'exists:fee_plans,id'],
            'enrollment_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(Enrollment::statusOptions()))],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! SectionScope::canAccessAcademicYearSection(
                $this->user(),
                $this->integer('academic_year_id'),
                $this->integer('section_id')
            )) {
                $validator->errors()->add('section_id', 'You are not assigned to manage enrollments for the selected class.');
                return;
            }

            $sectionId = $this->input('section_id');
            $gradeId = $this->input('grade_id');

            if ($sectionId && $gradeId) {
                $section = Section::query()->find($sectionId);

                if ($section && (int) $section->grade_id !== (int) $gradeId) {
                    $validator->errors()->add('section_id', 'The selected section does not belong to the selected grade.');
                }
            }

            $duplicate = Enrollment::query()
                ->where('student_id', $this->input('student_id'))
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('academic_year_id', 'This student is already enrolled in the selected academic year.');
            }

            if ($this->filled('fee_plan_id')) {
                $feePlan = FeePlan::query()->find($this->input('fee_plan_id'));

                if ($feePlan && (int) $feePlan->academic_year_id !== (int) $this->input('academic_year_id')) {
                    $validator->errors()->add('fee_plan_id', 'The selected fee plan must belong to the selected academic year.');
                }
            }
        });
    }
}
