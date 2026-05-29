<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use App\Support\SectionScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'attendance_date' => ['required', 'date'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'exists:students,id'],
            'attendances.*.status' => ['required', 'in:present,absent,late'],
            'attendances.*.remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $sectionIds = SectionScope::accessibleSectionIds($this->user(), $this->integer('academic_year_id'));

            if ($sectionIds !== null && ! $sectionIds->contains($this->integer('section_id'))) {
                $validator->errors()->add('section_id', 'You are not assigned to record attendance for the selected class.');
                return;
            }

            $studentIds = collect($this->input('attendances', []))
                ->pluck('student_id')
                ->filter()
                ->map(fn ($studentId) => (int) $studentId);

            if ($studentIds->isEmpty()) {
                return;
            }

            $validStudentIds = Enrollment::query()
                ->where('academic_year_id', $this->integer('academic_year_id'))
                ->where('section_id', $this->integer('section_id'))
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->whereIn('student_id', $studentIds->all())
                ->pluck('student_id')
                ->map(fn ($studentId) => (int) $studentId);

            $invalidStudentIds = $studentIds->diff($validStudentIds);

            if ($invalidStudentIds->isNotEmpty()) {
                $validator->errors()->add('attendances', 'One or more students are not actively enrolled in the selected class.');
            }
        });
    }
}
