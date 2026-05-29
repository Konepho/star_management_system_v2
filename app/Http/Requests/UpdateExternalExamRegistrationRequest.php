<?php

namespace App\Http\Requests;

use App\Models\ExternalExamRegistration;
use App\Models\ExternalExamSession;
use Illuminate\Validation\Validator;

class UpdateExternalExamRegistrationRequest extends StoreExternalExamRegistrationRequest
{
    public function withValidator(Validator $validator): void
    {
        /** @var ExternalExamRegistration $registration */
        $registration = $this->route('external_exam_registration');

        $validator->after(function (Validator $validator) use ($registration): void {
            if ((float) $this->input('discount_amount', 0) > (float) $this->input('fee_amount', 0)) {
                $validator->errors()->add('discount_amount', 'Discount cannot be greater than the external exam fee.');
            }

            $duplicate = ExternalExamRegistration::query()
                ->where('student_id', $this->input('student_id'))
                ->where('external_exam_session_id', $this->input('external_exam_session_id'))
                ->where('status', '!=', ExternalExamRegistration::STATUS_CANCELLED)
                ->whereKeyNot($registration->id)
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
