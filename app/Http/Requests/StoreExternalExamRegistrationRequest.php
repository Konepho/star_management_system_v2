<?php

namespace App\Http\Requests;

use App\Models\ExternalExamRegistration;
use App\Models\ExternalExamSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExternalExamRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $feeAmount = (float) ($this->input('fee_amount') ?: 0);
        $discountAmount = (float) ($this->input('discount_amount') ?: 0);

        $this->merge([
            'fee_amount' => $feeAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => max(0, $feeAmount - $discountAmount),
        ]);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'external_exam_session_id' => ['required', 'exists:external_exam_sessions,id'],
            'registration_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(ExternalExamRegistration::statusOptions()))],
            'fee_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'candidate_no' => ['nullable', 'string', 'max:255'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'grade' => ['nullable', 'string', 'max:255'],
            'result_status' => ['required', Rule::in(array_keys(ExternalExamRegistration::resultStatusOptions()))],
            'result_remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ((float) $this->input('discount_amount', 0) > (float) $this->input('fee_amount', 0)) {
                $validator->errors()->add('discount_amount', 'Discount cannot be greater than the external exam fee.');
            }

            $duplicate = ExternalExamRegistration::query()
                ->where('student_id', $this->input('student_id'))
                ->where('external_exam_session_id', $this->input('external_exam_session_id'))
                ->where('status', '!=', ExternalExamRegistration::STATUS_CANCELLED)
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('external_exam_session_id', 'This student is already registered for the selected external exam session.');
            }

            $session = ExternalExamSession::query()->find($this->input('external_exam_session_id'));

            if ($session && $session->status === ExternalExamSession::STATUS_CANCELLED) {
                $validator->errors()->add('external_exam_session_id', 'Cancelled external exam sessions cannot accept registrations.');
            }
        });
    }
}
