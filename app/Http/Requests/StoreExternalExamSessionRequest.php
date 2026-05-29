<?php

namespace App\Http\Requests;

use App\Models\ExternalExamSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExternalExamSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:255'],
            'exam_body' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:255'],
            'exam_date' => ['nullable', 'date'],
            'registration_deadline' => ['nullable', 'date'],
            'fee_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(array_keys(ExternalExamSession::statusOptions()))],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
