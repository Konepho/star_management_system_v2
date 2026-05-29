<?php

namespace App\Http\Requests;

use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStudentPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_invoice_id' => ['required', 'exists:student_invoices,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(array_keys(StudentPayment::methodOptions()))],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var StudentInvoice|null $invoice */
            $invoice = StudentInvoice::query()->with('payments')->find($this->input('student_invoice_id'));

            if (! $invoice) {
                return;
            }

            if (in_array($invoice->status, ['cancelled', 'void', 'draft'], true)) {
                $validator->errors()->add('student_invoice_id', 'Payments cannot be collected for draft, cancelled, or void invoices.');
            }

            if ((float) $this->input('amount', 0) > $invoice->balance_due) {
                $validator->errors()->add('amount', 'Payment amount cannot be greater than the remaining invoice balance.');
            }
        });
    }
}
