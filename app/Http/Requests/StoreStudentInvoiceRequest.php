<?php

namespace App\Http\Requests;

use App\Models\FeeItem;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentExtraClassRegistration;
use App\Models\StudentInvoice;
use Illuminate\Database\Eloquent\Builder;
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
            'internal_notes' => ['nullable', 'string'],
            'fee_item_ids' => ['nullable', 'array'],
            'fee_item_ids.*' => ['integer', 'exists:fee_items,id'],
            'extra_registration_ids' => ['nullable', 'array'],
            'extra_registration_ids.*' => ['integer', 'exists:student_extra_class_registrations,id'],
            'fee_item_quantities' => ['nullable', 'array'],
            'fee_item_quantities.*' => ['nullable', 'integer', 'min:1'],
            'excluded_academic_charge_keys' => ['nullable', 'array'],
            'excluded_academic_charge_keys.*' => ['string', 'max:100'],
            'excluded_academic_charge_reasons' => ['nullable', 'array'],
            'excluded_academic_charge_reasons.*' => ['nullable', 'string', 'max:255'],
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
            'extra_registration_ids' => collect($this->input('extra_registration_ids', []))
                ->filter(fn ($id) => filled($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all(),
            'fee_item_quantities' => collect($this->input('fee_item_quantities', []))
                ->map(fn ($quantity) => filled($quantity) ? (int) $quantity : null)
                ->all(),
            'excluded_academic_charge_keys' => collect($this->input('excluded_academic_charge_keys', []))
                ->filter(fn ($key) => filled($key))
                ->map(fn ($key) => trim((string) $key))
                ->unique()
                ->values()
                ->all(),
            'excluded_academic_charge_reasons' => collect($this->input('excluded_academic_charge_reasons', []))
                ->mapWithKeys(fn ($reason, $key) => [trim((string) $key) => filled($reason) ? trim((string) $reason) : null])
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

            $feeItems = FeeItem::query()
                ->with('feeCategory')
                ->whereIn('id', $this->input('fee_item_ids', []))
                ->get();

            $extraRegistrations = StudentExtraClassRegistration::query()
                ->with('offering')
                ->whereIn('id', $this->input('extra_registration_ids', []))
                ->get();
            $billingPeriodType = $this->input('billing_period_type');
            $billingMonth = $this->input('billing_month');
            $billingQuarter = $this->input('billing_quarter');
            $billingYearLabel = $this->input('billing_year_label');
            $availableExtraRegistrations = StudentExtraClassRegistration::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->where('status', StudentExtraClassRegistration::STATUS_ACTIVE)
                ->whereDoesntHave('invoiceItems', function ($query) use ($billingPeriodType, $billingMonth, $billingQuarter, $billingYearLabel) {
                    $this->applyDuplicateExtraClassInvoicePeriodFilter(
                        $query->whereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->whereNotIn('status', [StudentInvoice::STATUS_CANCELLED, StudentInvoice::STATUS_VOID])),
                        $billingPeriodType,
                        $billingMonth,
                        $billingQuarter,
                        $billingYearLabel
                    );
                })
                ->exists();

            $enrollment = Enrollment::query()
                ->with(['feePlan.feeStructures'])
                ->where('student_id', $student->id)
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->first();
            $excludedAcademicChargeKeys = collect($this->input('excluded_academic_charge_keys', []));
            $availableAcademicChargeKeys = $enrollment
                ? $this->academicChargeKeysForEnrollment($enrollment)
                : collect();
            $hasOtherCharges = $feeItems->isNotEmpty();

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

            if ($feeItems->count() !== count($this->input('fee_item_ids', []))) {
                $validator->errors()->add('fee_item_ids', 'One or more selected fee items are invalid.');

                return;
            }

            if ($extraRegistrations->count() !== count($this->input('extra_registration_ids', []))) {
                $validator->errors()->add('extra_registration_ids', 'One or more selected extra class registrations are invalid.');

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

            foreach ($extraRegistrations as $registration) {
                if ((int) $registration->student_id !== (int) $student->id) {
                    $validator->errors()->add('extra_registration_ids', 'Selected extra class registrations must belong to the selected student.');
                    return;
                }

                if ((int) $registration->academic_year_id !== (int) $this->input('academic_year_id')) {
                    $validator->errors()->add('extra_registration_ids', 'Selected extra class registrations must belong to the selected academic year.');
                    return;
                }

                if ($registration->status !== StudentExtraClassRegistration::STATUS_ACTIVE) {
                    $validator->errors()->add('extra_registration_ids', 'Only active extra class registrations can be billed.');
                    return;
                }

                $alreadyBilled = $this->applyDuplicateExtraClassInvoicePeriodFilter(
                    $registration->invoiceItems()
                        ->whereHas('invoice', fn ($query) => $query->whereNotIn('status', [StudentInvoice::STATUS_CANCELLED, StudentInvoice::STATUS_VOID])),
                    $billingPeriodType,
                    $billingMonth,
                    $billingQuarter,
                    $billingYearLabel
                )->exists();

                if ($alreadyBilled) {
                    $validator->errors()->add('extra_registration_ids', 'One or more selected extra class registrations have already been billed for the selected billing period.');
                    return;
                }
            }

            if ($excludedAcademicChargeKeys->diff($availableAcademicChargeKeys)->isNotEmpty()) {
                $validator->errors()->add('excluded_academic_charge_keys', 'One or more removed academic fee rows are invalid for this invoice.');

                return;
            }

            if (! $enrollment && $extraRegistrations->isEmpty() && ! $hasOtherCharges) {
                $validator->errors()->add(
                    'academic_year_id',
                    $availableExtraRegistrations
                        ? 'This student has extra class registrations in the selected academic year, but you still need to tick at least one item under Extra Class Charges before generating the invoice.'
                        : 'This student needs either an active enrollment, at least one active extra class registration, or at least one fee item in the selected academic year before you can generate an invoice.'
                );

                return;
            }

            if ($enrollment && ! $enrollment->feePlan && $extraRegistrations->isEmpty() && ! $hasOtherCharges) {
                $validator->errors()->add('academic_year_id', 'This enrollment does not have a fee plan yet. Assign a fee plan before generating an invoice, or bill the student through active extra class registrations only.');

                return;
            }

            $activePlanStructures = $enrollment?->feePlan
                ? $enrollment->feePlan->feeStructures->where('status', 'active')
                : collect();
            $remainingAcademicChargeCount = max(0, $availableAcademicChargeKeys->count() - $excludedAcademicChargeKeys->count());

            if ($remainingAcademicChargeCount === 0 && $feeItems->isEmpty() && $extraRegistrations->isEmpty()) {
                $validator->errors()->add('academic_year_id', 'There are no billable fee-plan charges, fee items, or extra class registrations for this student and academic year.');
                return;
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($remainingAcademicChargeCount > 0 && $enrollment?->id) {
                $academicDuplicateExists = $this->duplicateInvoicePeriodQuery($student, (int) $this->input('academic_year_id'), $billingPeriodType, $billingMonth, $billingQuarter, $billingYearLabel)
                    ->where('enrollment_id', $enrollment->id)
                    ->whereHas('items', fn (Builder $query) => $query->whereNotNull('fee_structure_id'))
                    ->exists();

                if ($academicDuplicateExists) {
                    $validator->errors()->add('billing_period_type', 'An academic invoice already exists for this student and billing period.');

                    return;
                }
            }

            if ($feeItems->isNotEmpty() && $this->shouldProtectFeeItemsByBillingPeriod($billingPeriodType)) {
                $duplicateFeeItemExists = $this->duplicateInvoicePeriodQuery($student, (int) $this->input('academic_year_id'), $billingPeriodType, $billingMonth, $billingQuarter, $billingYearLabel)
                    ->whereHas('items', fn (Builder $query) => $query->whereIn('fee_item_id', $feeItems->pluck('id')))
                    ->exists();

                if ($duplicateFeeItemExists) {
                    $validator->errors()->add('fee_item_ids', 'One or more selected fee items are already included on another active invoice for this billing period.');
                }
            }
        });
    }

    protected function academicChargeKeysForEnrollment(Enrollment $enrollment)
    {
        if (! $enrollment->feePlan) {
            return collect();
        }

        return $enrollment->feePlan->feeStructures
            ->where('status', 'active')
            ->flatMap(function ($feeStructure) {
                if ($feeStructure->billing_cycle === 'installment') {
                    return $feeStructure->installments->map(
                        fn ($installment) => $this->academicChargeKey($feeStructure->id, $installment->installment_no)
                    );
                }

                return [$this->academicChargeKey($feeStructure->id, null)];
            })
            ->values();
    }

    protected function academicChargeKey(int $feeStructureId, ?int $installmentNo): string
    {
        return 'fee-structure:' . $feeStructureId . ':installment:' . ($installmentNo ?? 'base');
    }

    protected function applyDuplicateExtraClassInvoicePeriodFilter($query, ?string $billingPeriodType, ?string $billingMonth, ?string $billingQuarter, ?string $billingYearLabel)
    {
        return match ($billingPeriodType) {
            StudentInvoice::PERIOD_MONTHLY => $query->whereHas('invoice', fn ($invoiceQuery) => $invoiceQuery
                ->where('billing_period_type', StudentInvoice::PERIOD_MONTHLY)
                ->where('billing_month', $billingMonth)),
            StudentInvoice::PERIOD_QUARTERLY => $query->whereHas('invoice', fn ($invoiceQuery) => $invoiceQuery
                ->where('billing_period_type', StudentInvoice::PERIOD_QUARTERLY)
                ->where('billing_quarter', $billingQuarter)),
            default => $query->whereHas('invoice', fn ($invoiceQuery) => $invoiceQuery
                ->where('billing_period_type', $billingPeriodType)
                ->where('billing_year_label', $billingYearLabel)),
        };
    }

    protected function duplicateInvoicePeriodQuery(Student $student, int $academicYearId, ?string $billingPeriodType, ?string $billingMonth, ?string $billingQuarter, ?string $billingYearLabel): Builder
    {
        $query = StudentInvoice::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->where('billing_period_type', $billingPeriodType)
            ->whereNotIn('status', [StudentInvoice::STATUS_CANCELLED, StudentInvoice::STATUS_VOID]);

        return match ($billingPeriodType) {
            StudentInvoice::PERIOD_MONTHLY => $query->where('billing_month', $billingMonth),
            StudentInvoice::PERIOD_QUARTERLY => $query->where('billing_quarter', $billingQuarter),
            default => $query->where('billing_year_label', $billingYearLabel),
        };
    }

    protected function shouldProtectFeeItemsByBillingPeriod(?string $billingPeriodType): bool
    {
        return in_array($billingPeriodType, [
            StudentInvoice::PERIOD_MONTHLY,
            StudentInvoice::PERIOD_QUARTERLY,
            StudentInvoice::PERIOD_ACADEMIC_YEAR,
        ], true);
    }
}
