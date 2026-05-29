<?php

namespace App\Http\Requests;

use App\Models\FeeStructure;
use App\Models\Grade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateFeeStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var FeeStructure $feeStructure */
        $feeStructure = $this->route('fee_structure');

        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'grade_group' => ['nullable', Rule::in(array_keys(Grade::groupOptions()))],
            'fee_scope' => ['required', 'in:all,group,specific'],
            'fee_category_id' => ['required', 'exists:fee_categories,id'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,annual,one-time,installment'],
            'is_optional' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive'],
            'remarks' => ['nullable', 'string'],
            'installments' => ['nullable', 'array'],
            'installments.*.amount' => ['nullable', 'numeric', 'min:0.01'],
            'installments.*.due_date' => ['nullable', 'date'],
            'installments.*.remarks' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $installments = collect($this->input('installments', []))
            ->map(function ($installment) {
                return [
                    'amount' => data_get($installment, 'amount') === '' ? null : data_get($installment, 'amount'),
                    'due_date' => data_get($installment, 'due_date') ?: null,
                    'remarks' => data_get($installment, 'remarks') ?: null,
                ];
            })
            ->filter(function (array $installment): bool {
                return filled($installment['amount']) || filled($installment['due_date']) || filled($installment['remarks']);
            })
            ->values()
            ->all();

        $this->merge([
            'is_optional' => $this->boolean('is_optional'),
            'fee_scope' => $this->input('fee_scope') ?: 'all',
            'grade_id' => $this->input('grade_id') ?: null,
            'grade_group' => $this->input('grade_group') ?: null,
            'installments' => $installments,
        ]);

        if ($this->input('billing_cycle') === 'installment') {
            $this->merge([
                'amount' => collect($installments)->sum(fn (array $installment) => (float) $installment['amount']),
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        /** @var FeeStructure $feeStructure */
        $feeStructure = $this->route('fee_structure');

        $validator->after(function (Validator $validator) use ($feeStructure): void {
            if ($this->input('fee_scope') === 'specific' && blank($this->input('grade_id'))) {
                $validator->errors()->add('grade_id', 'Please select a grade for a specific-grade fee structure.');
            }

            if ($this->input('fee_scope') === 'group' && blank($this->input('grade_group'))) {
                $validator->errors()->add('grade_group', 'Please select a grade group for a group fee structure.');
            }

            if ($this->input('billing_cycle') !== 'installment' && blank($this->input('amount'))) {
                $validator->errors()->add('amount', 'Please enter the fee amount.');
            }

            if ($this->input('billing_cycle') === 'installment') {
                $installments = collect($this->input('installments', []));

                if ($installments->isEmpty()) {
                    $validator->errors()->add('installments', 'Please add at least one installment line.');
                }

                $installments->each(function (array $installment, int $index) use ($validator): void {
                    if (blank($installment['amount'] ?? null)) {
                        $validator->errors()->add("installments.$index.amount", 'Each installment must have an amount.');
                    }
                });
            }

            if (filled($this->input('grade_id'))) {
                $grade = Grade::query()->find($this->input('grade_id'));

                if ($grade && filled($this->input('grade_group')) && $grade->grade_group !== $this->input('grade_group')) {
                    $validator->errors()->add('grade_group', 'The selected grade does not belong to the chosen grade group.');
                }
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $duplicate = FeeStructure::query()
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->where('fee_category_id', $this->input('fee_category_id'))
                ->where('billing_cycle', $this->input('billing_cycle'))
                ->when($this->input('fee_scope') === 'specific', function ($query) {
                    $query->where('grade_id', $this->input('grade_id'));
                })
                ->when($this->input('fee_scope') === 'group', function ($query) {
                    $query->whereNull('grade_id')
                        ->where('grade_group', $this->input('grade_group'));
                })
                ->when($this->input('fee_scope') === 'all', function ($query) {
                    $query->whereNull('grade_id')
                        ->whereNull('grade_group');
                })
                ->whereKeyNot($feeStructure->id)
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('fee_category_id', 'This fee structure already exists for the selected academic year, grade scope, and billing cycle.');
            }
        });
    }

    public function passedValidation(): void
    {
        if ($this->input('fee_scope') === 'all') {
            $this->merge([
                'grade_id' => null,
                'grade_group' => null,
            ]);
        }

        if ($this->input('fee_scope') === 'group') {
            $this->merge([
                'grade_id' => null,
            ]);
        }

        if ($this->input('fee_scope') === 'specific') {
            $this->merge([
                'grade_group' => null,
            ]);
        }
    }
}
