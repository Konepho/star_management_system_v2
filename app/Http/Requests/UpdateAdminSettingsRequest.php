<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $studentFieldKeys = array_keys(config('id_cards.student_fields', []));
        $staffFieldKeys = array_keys(config('id_cards.staff_fields', []));

        return [
            'invoice_prefix' => ['required', 'string', 'max:20'],
            'invoice_padding' => ['required', 'integer', 'min:1', 'max:10'],
            'invoice_reset_scope' => ['required', Rule::in(['global', 'academic_year'])],
            'receipt_prefix' => ['required', 'string', 'max:20'],
            'receipt_padding' => ['required', 'integer', 'min:1', 'max:10'],
            'receipt_reset_scope' => ['required', Rule::in(['global', 'academic_year'])],
            'school_name' => ['required', 'string', 'max:120'],
            'school_phone' => ['nullable', 'string', 'max:30'],
            'school_email' => ['nullable', 'email', 'max:120'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'invoice_name_format' => ['required', Rule::in(['english_only', 'bilingual', 'preferred_then_english'])],
            'student_id_card_fields' => ['required', 'array', 'min:1'],
            'student_id_card_fields.*' => ['string', Rule::in($studentFieldKeys)],
            'staff_id_card_fields' => ['required', 'array', 'min:1'],
            'staff_id_card_fields.*' => ['string', Rule::in($staffFieldKeys)],
            'school_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_school_logo' => ['nullable', 'boolean'],
        ];
    }
}
