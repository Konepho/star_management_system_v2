<?php

namespace App\Http\Requests;

use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectionRequest extends FormRequest
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
        /** @var Section $section */
        $section = $this->route('section');

        return [
            'grade_id' => ['required', 'exists:grades,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sections', 'code')
                    ->where(fn ($query) => $query->where('grade_id', $this->input('grade_id')))
                    ->ignore($section->id),
            ],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:active,draft,closed'],
        ];
    }
}
