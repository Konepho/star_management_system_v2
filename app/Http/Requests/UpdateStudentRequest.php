<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /** @var Student $student */
        $student = $this->route('student');

        return [
            'admission_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('students', 'admission_no')->ignore($student->id),
            ],
            'name_mm' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'student_type' => ['nullable', 'in:new,old'],
            'previous_school_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'admission_date' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'card_color' => ['required', 'in:yellow,red,blue,green,orange'],
            'status' => ['required', 'in:active,inactive,graduated,transferred,archived'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'father_phone' => ['nullable', 'string', 'max:255'],
            'father_email' => ['nullable', 'email', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'mother_phone' => ['nullable', 'string', 'max:255'],
            'mother_email' => ['nullable', 'email', 'max:255'],
            'blood_type' => ['nullable', 'string', 'max:50'],
            'allergies' => ['nullable', 'string'],
            'medical_conditions' => ['nullable', 'string'],
            'medications' => ['nullable', 'string'],
            'doctor_name' => ['nullable', 'string', 'max:255'],
            'doctor_phone' => ['nullable', 'string', 'max:255'],
            'emergency_medical_note' => ['nullable', 'string'],
            'health_remark' => ['nullable', 'string'],
        ];
    }
}
