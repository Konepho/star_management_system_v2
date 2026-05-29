<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Student;
use App\Support\SectionScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMarkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_id' => ['required', 'exists:exams,id'],
            'student_id' => ['required', 'exists:students,id'],
            'subject_id' => [
                'required',
                'exists:subjects,id',
                Rule::unique('marks')->where(fn ($query) => $query
                    ->where('exam_id', $this->input('exam_id'))
                    ->where('student_id', $this->input('student_id'))),
            ],
            'score' => ['required', 'numeric', 'min:0'],
            'max_score' => ['required', 'numeric', 'gt:0'],
            'grade_letter' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'in:draft,published,reviewed'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $exam = Exam::find($this->input('exam_id'));
            $student = Student::find($this->input('student_id'));

            if ($exam && $student) {
                $hasMatchingEnrollment = Enrollment::query()
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $exam->academic_year_id)
                    ->where('status', Enrollment::STATUS_ACTIVE)
                    ->exists();

                if (! $hasMatchingEnrollment) {
                    $validator->errors()->add('student_id', 'The selected student does not belong to the exam academic year.');
                }

                $accessibleSectionIds = SectionScope::accessibleSectionIds($this->user(), $exam->academic_year_id);

                if ($accessibleSectionIds !== null) {
                    if ($accessibleSectionIds->isEmpty()) {
                        $validator->errors()->add('student_id', 'You are not assigned to enter marks for this class.');
                    } else {
                        $isWithinAssignedSections = Enrollment::query()
                            ->where('student_id', $student->id)
                            ->where('academic_year_id', $exam->academic_year_id)
                            ->where('status', Enrollment::STATUS_ACTIVE)
                            ->whereIn('section_id', $accessibleSectionIds->all())
                            ->exists();

                        if (! $isWithinAssignedSections) {
                            $validator->errors()->add('student_id', 'You are not assigned to enter marks for this class.');
                        }
                    }
                }
            }

            if ((float) $this->input('score') > (float) $this->input('max_score')) {
                $validator->errors()->add('score', 'The score cannot be greater than the maximum score.');
            }
        });
    }
}
