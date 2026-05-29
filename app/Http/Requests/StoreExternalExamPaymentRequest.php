<?php

namespace App\Http\Requests;

use App\Models\ExternalExamPayment;
use App\Models\ExternalExamRegistration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreExternalExamPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'external_exam_registration_id' => ['required', 'exists:external_exam_registrations,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(array_keys(ExternalExamPayment::methodOptions()))],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function registration(): ExternalExamRegistration
    {
        return ExternalExamRegistration::query()
            ->with(['session.academicYear', 'payments'])
            ->findOrFail($this->validated('external_exam_registration_id'));
    }

    public function ensureAmountWithinBalance(ExternalExamRegistration $registration): void
    {
        if ((float) $this->validated('amount') > $registration->balance_due) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount cannot be greater than the remaining external exam balance.',
            ]);
        }
    }
}
