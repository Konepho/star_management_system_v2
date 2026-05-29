<?php

namespace App\Http\Requests;

use App\Models\FeeItem;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStudentInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'status' => ['required', 'in:draft,issued'],
            'billing_period_type' => ['required', 'in:monthly,quarterly,academic_year,one_time,installment,custom'],
            'billing_month' => ['nullable', 'date_format:Y-m'],
            'billing_quarter' => ['nullable', 'in:Q1,Q2,Q3,Q4'],
            'billing_year_label' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'fee_item_ids' => ['nullable', 'array'],
            'fee_item_ids.*' => ['integer', 'exists:fee_items,id'],
            'fee_item_quantities' => ['nullable', 'array'],
            'fee_item_quantities.*' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $feeItemIds = collect($this->input('fee_item_ids', []))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'fee_item_ids' => $feeItemIds,
            'fee_item_quantities' => collect($this->input('fee_item_quantities', []))
                ->map(fn ($quantity) => filled($quantity) ? (int) $quantity : null)
                ->all(),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $student = Student::query()->find($this->input('student_id'));

            if (! $student) {
                return;
            }

            $enrollment = Enrollment::query()
                ->with(['feePlan.feeStructures'])
                ->where('student_id', $student->id)
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->first();

            if (! $enrollment) {
                $validator->errors()->add('academic_year_id', 'This student must have an active enrollment in the selected academic year before you can generate an invoice.');

                return;
            }

            if (! $enrollment->feePlan) {
                $validator->errors()->add('academic_year_id', 'This enrollment does not have a fee plan yet. Assign a fee plan before generating an invoice.');

                return;
            }

            $billingPeriodType = $this->input('billing_period_type');
            $billingMonth = $this->input('billing_month');
            $billingQuarter = $this->input('billing_quarter');

            if ($billingPeriodType === 'monthly' && ! $billingMonth) {
                $validator->errors()->add('billing_month', 'Select the billing month for monthly invoices.');
            }

            if ($billingPeriodType === 'quarterly' && ! $billingQuarter) {
                $validator->errors()->add('billing_quarter', 'Select the billing quarter for quarterly invoices.');
            }

            if ($billingPeriodType === 'academic_year' && ! $this->filled('billing_year_label')) {
                $validator->errors()->add('billing_year_label', 'Provide a billing label for academic year invoices.');
            }

            if ($billingPeriodType === 'custom' && ! $this->filled('billing_year_label')) {
                $validator->errors()->add('billing_year_label', 'Provide a billing label for custom invoices.');
            }

            $activePlanStructures = $enrollment->feePlan->feeStructures
                ->where('status', 'active');

            if ($activePlanStructures->isEmpty() && $this->input('fee_item_ids', []) === []) {
                $validator->errors()->add('academic_year_id', 'The assigned fee plan has no active fee structures. Add fee structures to the plan or select at least one fee item.');
                return;
            }

            $feeItems = FeeItem::query()
                ->with('feeCategory')
                ->whereIn('id', $this->input('fee_item_ids', []))
                ->get();

            if ($feeItems->count() !== count($this->input('fee_item_ids', []))) {
                $validator->errors()->add('fee_item_ids', 'One or more selected fee items are invalid.');

                return;
            }

            foreach ($feeItems as $feeItem) {
                if ($feeItem->status !== 'active') {
                    $validator->errors()->add('fee_item_ids', 'Only active fee items can be added to an invoice.');
                    return;
                }

                $quantity = (int) ($this->input("fee_item_quantities.{$feeItem->id}") ?: 1);

                if ($quantity < 1) {
                    $validator->errors()->add("fee_item_quantities.{$feeItem->id}", 'Material item quantity must be at least 1.');
                    return;
                }
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $duplicateQuery = StudentInvoice::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->where('billing_period_type', $billingPeriodType)
                ->whereNotIn('status', ['cancelled', 'void']);

            if ($enrollment->id) {
                $duplicateQuery->where('enrollment_id', $enrollment->id);
            }

            if ($billingPeriodType === 'monthly') {
                $duplicateQuery->where('billing_month', $billingMonth);
            } elseif ($billingPeriodType === 'quarterly') {
                $duplicateQuery->where('billing_quarter', $billingQuarter);
            } else {
                $duplicateQuery->where('billing_year_label', $this->input('billing_year_label'));
            }

            if ($duplicateQuery->exists()) {
                $validator->errors()->add('billing_period_type', 'An active invoice already exists for this student enrollment and billing period.');
            }
        });
    }
}
