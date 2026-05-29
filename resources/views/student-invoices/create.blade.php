@php
    $studentOptions = $students->map(function ($student) {
        $activeEnrollment = $student->enrollments->firstWhere('status', \App\Models\Enrollment::STATUS_ACTIVE);

        return [
            'id' => (string) $student->id,
            'label' => trim(
                $student->full_name
                . ' - ' . $student->admission_no
                . ($activeEnrollment?->academicYear ? ' - ' . $activeEnrollment->academicYear->name : '')
                . ($activeEnrollment?->grade ? ' - ' . $activeEnrollment->grade->name : '')
                . ($activeEnrollment?->feePlan ? ' - ' . $activeEnrollment->feePlan->name : ' - No Fee Plan')
            ),
        ];
    })->values();
    $selectedStudent = $students->firstWhere('id', old('student_id'));
    $initialStudentLabel = $selectedStudent
        ? $studentOptions->firstWhere('id', (string) $selectedStudent->id)['label'] ?? ''
        : '';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Generate Student Invoice') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form
                        method="POST"
                        action="{{ route('student-invoices.store') }}"
                        class="space-y-6"
                        x-data="{
                            selectedStudentId: @js((string) old('student_id', '')),
                            selectedAcademicYearId: @js((string) old('academic_year_id', $invoice->academic_year_id ?? '')),
                            summaries: @js($enrollmentSummaries),
                            get enrollmentKey() {
                                if (! this.selectedStudentId || ! this.selectedAcademicYearId) {
                                    return null;
                                }

                                return `${this.selectedStudentId}:${this.selectedAcademicYearId}`;
                            },
                            get enrollmentSummary() {
                                return this.enrollmentKey ? (this.summaries[this.enrollmentKey] ?? null) : null;
                            }
                        }"
                    >
                        @csrf

                        <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                            Invoice billing now comes from the student's active enrollment and assigned fee plan. Choose the student and academic year here, and the system will automatically use the enrollment fee plan. Add fee items below only for extra materials like books or uniforms.
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <x-searchable-student-select
                                    x-model="selectedStudentId"
                                    :selected-id="old('student_id', '')"
                                    :initial-label="$initialStudentLabel"
                                    :options="$studentOptions"
                                    helper="Type a student name or admission number. Students must already have an active enrollment and fee plan for the selected academic year."
                                />
                            </div>

                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                                <select id="academic_year_id" name="academic_year_id" x-model="selectedAcademicYearId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">Select Academic Year</option>
                                    @foreach ($academicYears as $academicYear)
                                        <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $invoice->academic_year_id) === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('academic_year_id')" />
                            </div>

                            <div>
                                <x-input-label for="issue_date" :value="__('Issue Date')" />
                                <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full" :value="old('issue_date', optional($invoice->issue_date)->format('Y-m-d') ?? $invoice->issue_date)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('issue_date')" />
                            </div>

                            <div>
                                <x-input-label for="due_date" :value="__('Due Date')" />
                                <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date')" />
                                <x-input-error class="mt-2" :messages="$errors->get('due_date')" />
                            </div>

                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                @php($selectedStatus = old('status', $invoice->status ?: 'issued'))
                                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    @foreach (\App\Models\StudentInvoice::creatableStatusOptions() as $statusValue => $statusLabel)
                                        <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('status')" />
                            </div>

                            <div>
                                <x-input-label for="billing_period_type" :value="__('Billing Period Type')" />
                                @php($selectedBillingPeriodType = old('billing_period_type', \App\Models\StudentInvoice::PERIOD_MONTHLY))
                                <select id="billing_period_type" name="billing_period_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    @foreach (\App\Models\StudentInvoice::billingPeriodTypeOptions() as $billingTypeValue => $billingTypeLabel)
                                        <option value="{{ $billingTypeValue }}" @selected($selectedBillingPeriodType === $billingTypeValue)>{{ $billingTypeLabel }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('billing_period_type')" />
                            </div>

                            <div>
                                <x-input-label for="billing_month" :value="__('Billing Month')" />
                                <x-text-input id="billing_month" name="billing_month" type="month" class="mt-1 block w-full" :value="old('billing_month')" />
                                <x-input-error class="mt-2" :messages="$errors->get('billing_month')" />
                            </div>

                            <div>
                                <x-input-label for="billing_quarter" :value="__('Billing Quarter')" />
                                @php($selectedBillingQuarter = old('billing_quarter'))
                                <select id="billing_quarter" name="billing_quarter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Quarter</option>
                                    @foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $quarter)
                                        <option value="{{ $quarter }}" @selected($selectedBillingQuarter === $quarter)>{{ $quarter }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('billing_quarter')" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="billing_year_label" :value="__('Billing Label')" />
                                <x-text-input id="billing_year_label" name="billing_year_label" type="text" class="mt-1 block w-full" :value="old('billing_year_label')" placeholder="Example: 2026-2027, June 2026, Q1 2026-2027" />
                                <x-input-error class="mt-2" :messages="$errors->get('billing_year_label')" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="notes" :value="__('Notes')" />
                                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-slate-900">Enrollment Billing Summary</h3>
                                    <p class="mt-1 text-sm text-slate-600">This is the enrollment record the invoice will use for class placement and fee-plan billing.</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" x-show="enrollmentSummary" x-cloak>Auto-loaded from enrollment</span>
                            </div>

                            <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4" x-show="enrollmentSummary" x-cloak>
                                <div class="rounded-lg bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Grade</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="enrollmentSummary?.grade || '—'"></div>
                                </div>
                                <div class="rounded-lg bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Section</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="enrollmentSummary?.section || '—'"></div>
                                </div>
                                <div class="rounded-lg bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Fee Plan</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="enrollmentSummary?.fee_plan || 'No fee plan'"></div>
                                </div>
                                <div class="rounded-lg bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Plan Fee Structures</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="enrollmentSummary?.fee_structures_count ?? 0"></div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600" x-show="! enrollmentSummary">
                                Select a student and academic year to see the enrollment, class, section, and fee plan that will be used for this invoice.
                            </div>

                            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800" x-show="enrollmentSummary && ! enrollmentSummary.has_fee_plan" x-cloak>
                                This enrollment does not have a fee plan yet. Assign a fee plan in Enrollments before generating the invoice.
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Fee Plan Billing</h3>
                                <p class="mt-1 text-sm text-slate-600">Core invoice charges are loaded automatically from the student's active enrollment fee plan. Use the student invoice preview screen from a student record whenever you want to inspect the plan charges before billing.</p>
                            </div>

                            <x-input-error class="mt-4" :messages="$errors->get('academic_year_id')" />

                            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-600">
                                The system will include all active fee structures linked to the student's enrollment fee plan for the selected academic year, including installment lines when applicable.
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-slate-900">Fee Items</h3>
                                    <p class="mt-1 text-sm text-slate-600">Add material charges like uniform sizes, books, and stationery packs to this invoice.</p>
                                </div>
                            </div>

                            <x-input-error class="mt-4" :messages="$errors->get('fee_item_ids')" />
                            <div class="mt-4 grid gap-3">
                                @foreach ($feeItems as $feeItem)
                                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <label class="flex items-start gap-3">
                                                <input type="checkbox" name="fee_item_ids[]" value="{{ $feeItem->id }}" @checked(in_array($feeItem->id, old('fee_item_ids', []))) class="mt-1 rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500">
                                                <span class="block">
                                                    <span class="block text-sm font-semibold text-slate-900">
                                                        {{ $feeItem->name }}
                                                        @if ($feeItem->variant)
                                                            - {{ $feeItem->variant }}
                                                        @endif
                                                    </span>
                                                    <span class="mt-1 block text-xs text-slate-500">
                                                        {{ $feeItem->feeCategory?->name }} | Unit Price {{ number_format((float) $feeItem->price, 2) }} | {{ $feeItem->discount_policy_label }}
                                                    </span>
                                                </span>
                                            </label>

                                            <div class="w-full sm:w-32">
                                                <x-input-label :for="'fee_item_quantity_'.$feeItem->id" :value="__('Quantity')" />
                                                <x-text-input
                                                    :id="'fee_item_quantity_'.$feeItem->id"
                                                    :name="'fee_item_quantities['.$feeItem->id.']'"
                                                    type="number"
                                                    min="1"
                                                    class="mt-1 block w-full"
                                                    :value="old('fee_item_quantities.'.$feeItem->id, 1)"
                                                />
                                                <x-input-error class="mt-2" :messages="$errors->get('fee_item_quantities.'.$feeItem->id)" />
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <x-primary-button>Generate Invoice</x-primary-button>
                            <a href="{{ route('student-invoices.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
