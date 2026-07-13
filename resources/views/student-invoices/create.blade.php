@php
    $academicYearOptions = $academicYears->map(fn ($academicYear) => [
        'id' => (string) $academicYear->id,
        'name' => $academicYear->name,
    ])->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Create Student Invoice') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div
                x-data="invoiceBuilder()"
                class="max-w-5xl"
            >
                <form method="POST" action="{{ route('student-invoices.store') }}" class="space-y-6">
                    @csrf

                    <input type="hidden" name="status" :value="submissionStatus">
                    <input type="hidden" name="billing_year_label" :value="effectiveBillingLabel">
                    <template x-for="item in removedFeePlanItems" :key="`excluded-${item.key}`">
                        <div>
                            <input type="hidden" name="excluded_academic_charge_keys[]" :value="item.key">
                            <input type="hidden" :name="`excluded_academic_charge_reasons[${item.key}]`" :value="academicChargeExclusionReasons[item.key] || ''">
                        </div>
                    </template>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">1. Student and Period</h3>
                        </div>

                        <div class="mt-5 grid gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <x-searchable-student-select
                                    x-model="selectedStudentId"
                                    :selected-id="old('student_id', '')"
                                    :initial-label="$initialStudentLabel"
                                    :options="$studentOptions"
                                    :search-url="$studentSearchUrl"
                                    helper="Type a student name or admission number. Students can be billed through a normal enrollment fee plan, active extra class registrations, or both."
                                />
                            </div>

                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                                <select id="academic_year_id" name="academic_year_id" x-model="selectedAcademicYearId" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                                    <option value="">Select Academic Year</option>
                                    @foreach ($academicYears as $academicYear)
                                        <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $invoice->academic_year_id) === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('academic_year_id')" />
                            </div>

                            <div>
                                <x-input-label for="billing_period_type" :value="__('Billing Period')" />
                                @php($selectedBillingPeriodType = old('billing_period_type', \App\Models\StudentInvoice::PERIOD_MONTHLY))
                                <select id="billing_period_type" name="billing_period_type" x-model="billingPeriodType" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                                    @foreach (\App\Models\StudentInvoice::billingPeriodTypeOptions() as $billingTypeValue => $billingTypeLabel)
                                        <option value="{{ $billingTypeValue }}" @selected($selectedBillingPeriodType === $billingTypeValue)>{{ $billingTypeLabel }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('billing_period_type')" />
                            </div>

                            <div x-show="billingPeriodType === 'monthly'" x-cloak>
                                <x-input-label for="billing_month" :value="__('Billing Month')" />
                                <x-text-input id="billing_month" name="billing_month" x-model="billingMonth" type="month" class="mt-1 block w-full" :value="old('billing_month')" />
                                <x-input-error class="mt-2" :messages="$errors->get('billing_month')" />
                            </div>

                            <div x-show="billingPeriodType === 'quarterly'" x-cloak>
                                <x-input-label for="billing_quarter" :value="__('Billing Quarter')" />
                                @php($selectedBillingQuarter = old('billing_quarter'))
                                <select id="billing_quarter" name="billing_quarter" x-model="billingQuarter" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <option value="">Select Quarter</option>
                                    @foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $quarter)
                                        <option value="{{ $quarter }}" @selected($selectedBillingQuarter === $quarter)>{{ $quarter }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('billing_quarter')" />
                            </div>

                            <div x-show="billingPeriodType === 'custom'" x-cloak class="md:col-span-2">
                                <x-input-label for="custom_billing_label" :value="__('Custom Billing Label')" />
                                <x-text-input id="custom_billing_label" x-model="customBillingLabel" type="text" class="mt-1 block w-full" :value="old('billing_year_label')" placeholder="Example: Summer Camp 2026, Exam Retake June 2026" />
                                <x-input-error class="mt-2" :messages="$errors->get('billing_year_label')" />
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
                        </div>

                        <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Billing Label</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900" x-text="effectiveBillingLabel || 'Will be generated after you choose the billing period'"></div>
                            <p class="mt-1 text-xs text-slate-500">This label is created automatically for normal billing periods, so staff do not have to type it every time.</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">2. Academic Charges</h3>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" x-show="enrollmentSummary" x-cloak>Auto-loaded</span>
                        </div>

                        <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600" x-show="summaryLoading" x-cloak>
                            Loading student billing summary...
                        </div>

                        <div class="mt-4 rounded-xl border border-dashed border-rose-300 bg-rose-50 p-4 text-sm text-rose-700" x-show="summaryError && ! summaryLoading" x-cloak x-text="summaryError"></div>

                        <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600" x-show="! enrollmentSummary && ! summaryLoading && ! summaryError">
                            Select a student and academic year to load academic charges and extra class billing.
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4" x-show="enrollmentSummary" x-cloak>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Billing Source</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900" x-text="enrollmentSummary?.has_enrollment ? 'Academic + Optional Extras' : 'Extra Class Only Student'"></div>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Grade</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900" x-text="enrollmentSummary?.grade_label || enrollmentSummary?.grade || '—'"></div>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Section</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900" x-text="enrollmentSummary?.section_label || enrollmentSummary?.section || '—'"></div>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Fee Plan</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900" x-text="enrollmentSummary?.fee_plan_label || enrollmentSummary?.fee_plan || (enrollmentSummary?.has_enrollment ? 'No fee plan' : 'Not required')"></div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800" x-show="enrollmentSummary && enrollmentSummary.has_enrollment && ! enrollmentSummary.has_fee_plan && ! enrollmentSummary.extra_registration_count" x-cloak>
                            This enrollment does not have a fee plan yet. Assign a fee plan in Enrollments before billing, or bill the student with extra classes only.
                        </div>

                        <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800" x-show="enrollmentSummary && ! enrollmentSummary.has_enrollment && enrollmentSummary.extra_registration_count" x-cloak>
                            This student has no main enrollment for the selected academic year, but can still be billed through active extra class registrations only.
                        </div>

                        <div class="mt-4 space-y-3" x-show="enrollmentSummary?.fee_plan_items?.length" x-cloak>
                            <template x-for="item in (enrollmentSummary?.fee_plan_items || [])" :key="item.key">
                                <div class="rounded-xl border px-4 py-3" :class="isAcademicChargeExcluded(item.key) ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white'">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold" :class="isAcademicChargeExcluded(item.key) ? 'text-amber-900 line-through' : 'text-slate-900'" x-text="item.description"></div>
                                            <div class="text-xs text-slate-500" x-text="formatBillingCycle(item.billing_cycle)"></div>
                                            <div class="mt-1 text-xs text-slate-500" x-show="item.remarks" x-text="item.remarks"></div>
                                        </div>
                                        <div class="flex flex-col items-start gap-2 lg:items-end">
                                            <div class="text-sm font-semibold" :class="isAcademicChargeExcluded(item.key) ? 'text-amber-900' : 'text-slate-900'" x-text="formatCurrency(item.amount)"></div>
                                            <button
                                                type="button"
                                                @click="toggleAcademicCharge(item.key)"
                                                class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                                :class="isAcademicChargeExcluded(item.key) ? 'bg-white text-amber-800 hover:bg-amber-100 border border-amber-300' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200'"
                                            >
                                                <span x-text="isAcademicChargeExcluded(item.key) ? 'Undo Remove' : 'Remove from Invoice'"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-3" x-show="isAcademicChargeExcluded(item.key)" x-cloak>
                                        <x-input-label :for="'academic_exclusion_reason_template'" :value="__('Reason for Removal')" />
                                        <input
                                            type="text"
                                            x-model="academicChargeExclusionReasons[item.key]"
                                            placeholder="Optional reason, e.g. scholarship, defer this month"
                                            class="mt-1 block w-full rounded-xl border-amber-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                        >
                                        <p class="mt-1 text-xs text-amber-800">This row will be excluded from the current invoice only. The fee plan itself will stay unchanged.</p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-600" x-show="enrollmentSummary && (enrollmentSummary?.fee_plan_items?.length) && ! activeFeePlanItems.length">
                            No academic charges remain on this invoice. Keep at least one other charge before saving.
                        </div>

                        <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-600" x-show="enrollmentSummary && ! (enrollmentSummary?.fee_plan_items?.length)">
                            No academic fee-plan charges are available for this student and academic year.
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">3. Extra Class Charges</h3>
                        </div>

                        <x-input-error class="mt-4" :messages="$errors->get('extra_registration_ids')" />

                        <div class="mt-4 flex flex-wrap gap-3 text-xs font-medium text-slate-600" x-show="enrollmentSummary" x-cloak>
                            <span class="rounded-full bg-slate-100 px-3 py-1" x-text="`${availableExtraRegistrationsCount} available for this billing period`"></span>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-800" x-show="billedExtraRegistrationsCount > 0" x-cloak x-text="`${billedExtraRegistrationsCount} already billed for this billing period`"></span>
                        </div>

                        <div class="mt-4 space-y-3" x-show="enrollmentSummary?.extra_registrations?.length" x-cloak>
                            <template x-for="registration in (enrollmentSummary?.extra_registrations || [])" :key="registration.id">
                                <label class="flex items-start justify-between gap-4 rounded-xl border px-4 py-4" :class="isExtraRegistrationBilledForSelectedPeriod(registration) ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50'">
                                    <span class="flex items-start gap-3">
                                        <input
                                            type="checkbox"
                                            name="extra_registration_ids[]"
                                            :value="registration.id"
                                            x-model="selectedExtraRegistrationIds"
                                            :disabled="isExtraRegistrationBilledForSelectedPeriod(registration)"
                                            class="mt-1 rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500"
                                        >
                                        <span class="block">
                                            <span class="block text-sm font-semibold" :class="isExtraRegistrationBilledForSelectedPeriod(registration) ? 'text-amber-900' : 'text-slate-900'" x-text="registration.label"></span>
                                            <span class="mt-1 block text-xs text-slate-500">
                                                <span x-text="registration.fee_category || 'Extra Class Fee'"></span>
                                                <span> | Registered </span>
                                                <span x-text="registration.registered_on || '-'"></span>
                                            </span>
                                            <span class="mt-2 block text-xs font-medium text-amber-800" x-show="isExtraRegistrationBilledForSelectedPeriod(registration)" x-cloak>
                                                Already billed for this billing period.
                                            </span>
                                        </span>
                                    </span>
                                    <span class="text-sm font-semibold" :class="isExtraRegistrationBilledForSelectedPeriod(registration) ? 'text-amber-900' : 'text-slate-900'" x-text="formatCurrency(registration.fee_amount || 0)"></span>
                                </label>
                            </template>
                        </div>

                        <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600" x-show="enrollmentSummary?.extra_registrations?.length && ! availableExtraRegistrationsCount" x-cloak>
                            <span x-show="billedExtraRegistrationsCount > 0" x-cloak>
                                All extra class registrations for this student and academic year were already billed for the selected billing period, so there is nothing left to tick here.
                            </span>
                            <span x-show="! billedExtraRegistrationsCount">
                                No billable extra class registrations were found for this student and academic year.
                            </span>
                        </div>

                        <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600" x-show="! (enrollmentSummary?.extra_registrations?.length)">
                            No billable extra class registrations were found for this student and academic year.
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">4. Add Other Charges</h3>
                            <p class="mt-1 text-sm text-slate-600">Use these for registration fee, books, uniforms, stationery, or other optional items.</p>
                        </div>

                        <x-input-error class="mt-4" :messages="$errors->get('fee_item_ids')" />

                        <div class="mt-4 grid gap-3">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">Other Fee Items</div>
                                        <div class="mt-1 text-xs text-slate-500">Choose a fee item, set quantity, and add more rows when needed.</div>
                                    </div>
                                    <button type="button" @click="addOtherChargeRow()" class="inline-flex items-center justify-center rounded-lg border border-sky-300 px-3 py-2 text-sm font-semibold text-sky-700 transition hover:bg-sky-50">
                                        Add Item
                                    </button>
                                </div>

                                <div class="mt-4 space-y-3">
                                    <template x-for="row in otherChargeRows" :key="row.key">
                                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                                            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_180px_auto] lg:items-start">
                                                <div>
                                                    <x-input-label ::for="`other_fee_item_${row.key}`" :value="__('Fee Item')" />
                                                    <select
                                                        :id="`other_fee_item_${row.key}`"
                                                        x-model="row.feeItemId"
                                                        @change="syncFeeItemPayload()"
                                                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                                    >
                                                        <option value="">Select fee item</option>
                                                        <template x-for="option in availableFeeItemOptionsForRow(row.key)" :key="option.id">
                                                            <option :value="String(option.id)" x-text="`${option.label} | ${option.fee_category || 'Fee Item'} | ${formatCurrency(option.price)}`"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <div>
                                                    <x-input-label ::for="`other_fee_item_qty_${row.key}`" :value="__('Quantity')" />
                                                    <x-text-input
                                                        ::id="`other_fee_item_qty_${row.key}`"
                                                        type="number"
                                                        min="1"
                                                        class="mt-1 block w-full"
                                                        x-model="row.quantity"
                                                        @input="syncFeeItemPayload()"
                                                    />
                                                    <div class="mt-1 text-xs text-slate-500">
                                                        Line total <span class="font-semibold text-slate-700" x-text="formatCurrency(otherChargeRowLineAmount(row))"></span>
                                                    </div>
                                                </div>

                                                <div class="flex items-center justify-end gap-2 pt-7">
                                                    <button type="button" @click="addOtherChargeRow(row.key)" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                                        Add
                                                    </button>
                                                    <button type="button" @click="removeOtherChargeRow(row.key)" class="inline-flex items-center justify-center rounded-lg border border-rose-300 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50" x-show="otherChargeRows.length > 1">
                                                        Remove
                                                    </button>
                                                </div>
                                            </div>

                                            <template x-if="row.feeItemId && feeItemErrorMessages[String(row.feeItemId)]">
                                                <div class="mt-3 text-xs text-rose-600" x-text="feeItemErrorMessages[String(row.feeItemId)]"></div>
                                            </template>
                                        </div>
                                    </template>
                                </div>

                                <template x-for="feeItemId in selectedFeeItemIds" :key="`selected-fee-item-${feeItemId}`">
                                    <input type="hidden" name="fee_item_ids[]" :value="feeItemId">
                                </template>

                                <template x-for="feeItemId in selectedFeeItemIds" :key="`selected-fee-item-qty-${feeItemId}`">
                                    <input type="hidden" :name="`fee_item_quantities[${feeItemId}]`" :value="feeItemQuantities[String(feeItemId)] || 1">
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">5. Notes</h3>
                        </div>

                        <div class="mt-4">
                            <x-input-label for="notes" :value="__('Parent / Printable Notes')" />
                            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('notes') }}</textarea>
                            <p class="mt-1 text-xs text-slate-500">These notes can appear on the printed invoice for parents.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="internal_notes" :value="__('Internal Finance Notes')" />
                            <textarea id="internal_notes" name="internal_notes" rows="3" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('internal_notes') }}</textarea>
                            <p class="mt-1 text-xs text-slate-500">These notes stay inside the system and do not print for parents.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('internal_notes')" />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                            <div class="rounded-2xl bg-slate-900 px-4 py-4 text-white">
                                <div class="flex items-center justify-between gap-4 text-sm">
                                    <span class="text-slate-300">Academic</span>
                                    <span x-text="formatCurrency(feePlanTotal)"></span>
                                </div>
                                <div class="mt-2 flex items-center justify-between gap-4 text-sm">
                                    <span class="text-slate-300">Extra Class</span>
                                    <span x-text="formatCurrency(extraClassTotal)"></span>
                                </div>
                                <div class="mt-2 flex items-center justify-between gap-4 text-sm">
                                    <span class="text-slate-300">Other Charges</span>
                                    <span x-text="formatCurrency(feeItemTotal)"></span>
                                </div>
                                <div class="mt-4 border-t border-white/15 pt-3">
                                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Estimated Invoice Total</div>
                                    <div class="mt-2 text-2xl font-semibold" x-text="formatCurrency(grandTotal)"></div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <button type="submit" @click="submissionStatus = 'draft'" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    Save Draft
                                </button>
                                <button type="submit" @click="submissionStatus = 'issued'" class="inline-flex items-center justify-center rounded-xl bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-600">
                                    Issue Invoice
                                </button>
                                <a href="{{ route('student-invoices.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function invoiceBuilder() {
            return {
                selectedStudentId: @js((string) old('student_id', '')),
                selectedAcademicYearId: @js((string) old('academic_year_id', $invoice->academic_year_id ?? '')),
                billingPeriodType: @js(old('billing_period_type', \App\Models\StudentInvoice::PERIOD_MONTHLY)),
                billingMonth: @js(old('billing_month', '')),
                billingQuarter: @js(old('billing_quarter', '')),
                customBillingLabel: @js(old('billing_year_label', '')),
                submissionStatus: @js(old('status', $invoice->status ?: 'issued')),
                selectedExtraRegistrationIds: @js(collect(old('extra_registration_ids', []))->map(fn ($id) => (string) $id)->values()->all()),
                selectedFeeItemIds: @js(collect(old('fee_item_ids', []))->map(fn ($id) => (string) $id)->values()->all()),
                feeItemQuantities: @js(collect(old('fee_item_quantities', []))->mapWithKeys(fn ($quantity, $id) => [(string) $id => (int) ($quantity ?: 1)])->all()),
                otherChargeRows: [],
                nextOtherChargeRowKey: 0,
                excludedAcademicChargeKeys: @js(collect(old('excluded_academic_charge_keys', []))->map(fn ($key) => (string) $key)->values()->all()),
                academicChargeExclusionReasons: @js(collect(old('excluded_academic_charge_reasons', []))->mapWithKeys(fn ($reason, $key) => [(string) $key => (string) ($reason ?? '')])->all()),
                enrollmentSummary: @js($initialEnrollmentSummary),
                studentSummaryUrl: @js($studentSummaryUrl),
                summaryLoading: false,
                summaryError: '',
                feeItemOptions: @js($feeItemOptions),
                academicYears: @js($academicYearOptions),
                feeItemErrorMessages: @js(collect($errors->getMessages())
                    ->filter(fn ($messages, $key) => str_starts_with($key, 'fee_item_quantities.'))
                    ->mapWithKeys(fn ($messages, $key) => [str_replace('fee_item_quantities.', '', $key) => $messages[0] ?? ''])
                    ->all()),
                get selectedAcademicYearName() {
                    return this.academicYears.find((year) => year.id === this.selectedAcademicYearId)?.name || '';
                },
                get effectiveBillingLabel() {
                    if (this.billingPeriodType === 'monthly' && this.billingMonth) {
                        const [year, month] = this.billingMonth.split('-');
                        const date = new Date(Number(year), Number(month) - 1, 1);

                        return Number.isNaN(date.getTime())
                            ? this.billingMonth
                            : date.toLocaleString('en-US', { month: 'long', year: 'numeric' });
                    }

                    if (this.billingPeriodType === 'quarterly' && this.billingQuarter) {
                        return `${this.billingQuarter}${this.selectedAcademicYearName ? ' ' + this.selectedAcademicYearName : ''}`;
                    }

                    if (this.billingPeriodType === 'academic_year') {
                        return this.selectedAcademicYearName || this.customBillingLabel;
                    }

                    if (this.billingPeriodType === 'installment') {
                        return this.selectedAcademicYearName ? `Installment - ${this.selectedAcademicYearName}` : 'Installment';
                    }

                    if (this.billingPeriodType === 'one_time') {
                        return this.customBillingLabel || `One-Time Charge${this.selectedAcademicYearName ? ' - ' + this.selectedAcademicYearName : ''}`;
                    }

                    return this.customBillingLabel;
                },
                get feePlanItems() {
                    return this.enrollmentSummary?.fee_plan_items || [];
                },
                get activeFeePlanItems() {
                    return this.feePlanItems.filter((item) => ! this.isAcademicChargeExcluded(item.key));
                },
                get removedFeePlanItems() {
                    return this.feePlanItems.filter((item) => this.isAcademicChargeExcluded(item.key));
                },
                get feePlanTotal() {
                    return this.activeFeePlanItems.reduce((total, item) => total + Number(item.amount || 0), 0);
                },
                get selectedExtraRegistrationDetails() {
                    const registrations = this.enrollmentSummary?.extra_registrations || [];

                    return registrations.filter((registration) =>
                        this.selectedExtraRegistrationIds.includes(String(registration.id))
                        && ! this.isExtraRegistrationBilledForSelectedPeriod(registration)
                    );
                },
                get availableExtraRegistrationsCount() {
                    const registrations = this.enrollmentSummary?.extra_registrations || [];

                    return registrations.filter((registration) => ! this.isExtraRegistrationBilledForSelectedPeriod(registration)).length;
                },
                get billedExtraRegistrationsCount() {
                    const registrations = this.enrollmentSummary?.extra_registrations || [];

                    return registrations.filter((registration) => this.isExtraRegistrationBilledForSelectedPeriod(registration)).length;
                },
                get extraClassTotal() {
                    return this.selectedExtraRegistrationDetails.reduce((total, registration) => total + Number(registration.fee_amount || 0), 0);
                },
                feeItemLineAmount(id) {
                    const option = this.feeItemOptions.find((item) => String(item.id) === String(id));
                    const quantity = Math.max(1, Number(this.feeItemQuantities[String(id)] || 1));

                    return Number(option?.price || 0) * quantity;
                },
                createOtherChargeRow(feeItemId = '', quantity = 1, enabled = false) {
                    return {
                        key: `row-${this.nextOtherChargeRowKey++}`,
                        enabled,
                        feeItemId: feeItemId ? String(feeItemId) : '',
                        quantity: Math.max(1, Number(quantity || 1)),
                    };
                },
                addOtherChargeRow(afterRowKey = null) {
                    const row = this.createOtherChargeRow();

                    if (afterRowKey === null) {
                        this.otherChargeRows.push(row);
                    } else {
                        const index = this.otherChargeRows.findIndex((existingRow) => existingRow.key === afterRowKey);

                        if (index === -1) {
                            this.otherChargeRows.push(row);
                        } else {
                            this.otherChargeRows.splice(index + 1, 0, row);
                        }
                    }

                    this.syncFeeItemPayload();
                },
                removeOtherChargeRow(rowKey) {
                    if (this.otherChargeRows.length === 1) {
                        this.otherChargeRows = [this.createOtherChargeRow()];
                    } else {
                        this.otherChargeRows = this.otherChargeRows.filter((row) => row.key !== rowKey);
                    }

                    this.syncFeeItemPayload();
                },
                availableFeeItemOptionsForRow(rowKey) {
                    const currentRow = this.otherChargeRows.find((row) => row.key === rowKey);
                    const reservedIds = this.otherChargeRows
                        .filter((row) => row.key !== rowKey && row.enabled && row.feeItemId)
                        .map((row) => String(row.feeItemId));

                    return this.feeItemOptions.filter((option) =>
                        ! reservedIds.includes(String(option.id))
                        || String(currentRow?.feeItemId || '') === String(option.id)
                    );
                },
                otherChargeRowLineAmount(row) {
                    if (! row?.feeItemId) {
                        return 0;
                    }

                    const option = this.feeItemOptions.find((item) => String(item.id) === String(row.feeItemId));
                    const quantity = Math.max(1, Number(row.quantity || 1));

                    return Number(option?.price || 0) * quantity;
                },
                syncFeeItemPayload() {
                    const selectedIds = [];
                    const quantities = {};

                    this.otherChargeRows.forEach((row) => {
                        row.quantity = Math.max(1, Number(row.quantity || 1));

                        if (! row.feeItemId) {
                            return;
                        }

                        const feeItemId = String(row.feeItemId);

                        if (! selectedIds.includes(feeItemId)) {
                            selectedIds.push(feeItemId);
                            quantities[feeItemId] = 0;
                        }

                        quantities[feeItemId] += row.quantity;
                    });

                    this.selectedFeeItemIds = selectedIds;
                    this.feeItemQuantities = quantities;
                },
                fetchEnrollmentSummary() {
                    if (! this.selectedStudentId || ! this.selectedAcademicYearId) {
                        this.enrollmentSummary = null;
                        this.summaryError = '';
                        this.syncSelectedExtraRegistrations();
                        return;
                    }

                    const url = new URL(this.studentSummaryUrl, window.location.origin);
                    url.searchParams.set('student_id', this.selectedStudentId);
                    url.searchParams.set('academic_year_id', this.selectedAcademicYearId);
                    this.summaryLoading = true;
                    this.summaryError = '';

                    fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then((response) => response.ok ? response.json() : Promise.reject(response))
                        .then((payload) => {
                            this.enrollmentSummary = payload?.data || null;
                            this.syncSelectedExtraRegistrations();
                        })
                        .catch(() => {
                            this.enrollmentSummary = null;
                            this.summaryError = 'Unable to load the student billing summary right now.';
                            this.syncSelectedExtraRegistrations();
                        })
                        .finally(() => {
                            this.summaryLoading = false;
                        });
                },
                get selectedFeeItemDetails() {
                    return this.feeItemOptions
                        .filter((item) => this.selectedFeeItemIds.includes(String(item.id)))
                        .map((item) => ({
                            ...item,
                            quantity: Math.max(1, Number(this.feeItemQuantities[String(item.id)] || 1)),
                            amount: this.feeItemLineAmount(item.id),
                        }));
                },
                get feeItemTotal() {
                    return this.selectedFeeItemDetails.reduce((total, item) => total + Number(item.amount || 0), 0);
                },
                get grandTotal() {
                    return this.feePlanTotal + this.extraClassTotal + this.feeItemTotal;
                },
                get invoiceStream() {
                    const hasAcademic = this.activeFeePlanItems.length > 0;
                    const hasExtraClass = this.selectedExtraRegistrationDetails.length > 0;
                    const hasOther = this.selectedFeeItemDetails.length > 0;
                    const selectedSourceCount = [hasAcademic, hasExtraClass, hasOther].filter(Boolean).length;

                    if (selectedSourceCount > 1) {
                        return 'mixed';
                    }

                    if (hasAcademic) {
                        return 'academic';
                    }

                    if (hasExtraClass) {
                        return 'extra_class';
                    }

                    return 'other';
                },
                get invoiceStreamLabel() {
                    return ({
                        academic: 'Academic',
                        extra_class: 'Extra Class',
                        mixed: 'Mixed',
                        other: 'Other',
                    })[this.invoiceStream] || 'Other';
                },
                formatCurrency(value) {
                    return Number(value || 0).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },
                formatBillingCycle(cycle) {
                    return ({
                        'one-time': 'One-Time',
                        monthly: 'Monthly',
                        quarterly: 'Quarterly',
                        installment: 'Installment',
                        academic_year: 'Academic Year',
                    })[cycle] || cycle || 'Charge';
                },
                isAcademicChargeExcluded(key) {
                    return this.excludedAcademicChargeKeys.includes(String(key));
                },
                toggleAcademicCharge(key) {
                    key = String(key);

                    if (this.isAcademicChargeExcluded(key)) {
                        this.excludedAcademicChargeKeys = this.excludedAcademicChargeKeys.filter((existingKey) => existingKey !== key);
                        delete this.academicChargeExclusionReasons[key];
                        return;
                    }

                    this.excludedAcademicChargeKeys = [...this.excludedAcademicChargeKeys, key];
                    this.academicChargeExclusionReasons[key] = this.academicChargeExclusionReasons[key] || '';
                },
                currentExtraClassBillingKey() {
                    if (this.billingPeriodType === 'monthly' && this.billingMonth) {
                        return `monthly:${this.billingMonth}`;
                    }

                    if (this.billingPeriodType === 'quarterly' && this.billingQuarter) {
                        return `quarterly:${this.billingQuarter}`;
                    }

                    if (this.billingPeriodType === 'academic_year' && this.effectiveBillingLabel) {
                        return `academic_year:${this.effectiveBillingLabel}`;
                    }

                    if (this.billingPeriodType === 'installment' && this.effectiveBillingLabel) {
                        return `installment:${this.effectiveBillingLabel}`;
                    }

                    if (this.billingPeriodType === 'one_time' && this.effectiveBillingLabel) {
                        return `one_time:${this.effectiveBillingLabel}`;
                    }

                    if (this.billingPeriodType === 'custom' && this.effectiveBillingLabel) {
                        return `custom:${this.effectiveBillingLabel}`;
                    }

                    return null;
                },
                invoicePeriodKey(period) {
                    if (! period?.billing_period_type) {
                        return null;
                    }

                    if (period.billing_period_type === 'monthly' && period.billing_month) {
                        return `monthly:${period.billing_month}`;
                    }

                    if (period.billing_period_type === 'quarterly' && period.billing_quarter) {
                        return `quarterly:${period.billing_quarter}`;
                    }

                    if (period.billing_year_label) {
                        return `${period.billing_period_type}:${period.billing_year_label}`;
                    }

                    return `${period.billing_period_type}:`;
                },
                isExtraRegistrationBilledForSelectedPeriod(registration) {
                    const currentKey = this.currentExtraClassBillingKey();

                    if (! currentKey) {
                        return false;
                    }

                    return (registration.billed_periods || []).some((period) => this.invoicePeriodKey(period) === currentKey);
                },
                syncSelectedExtraRegistrations() {
                    const registrations = this.enrollmentSummary?.extra_registrations || [];
                    const allowedIds = registrations
                        .filter((registration) => ! this.isExtraRegistrationBilledForSelectedPeriod(registration))
                        .map((registration) => String(registration.id));

                    this.selectedExtraRegistrationIds = this.selectedExtraRegistrationIds.filter((id) => allowedIds.includes(String(id)));
                },
                init() {
                    const selectedRows = this.selectedFeeItemIds.map((id) => this.createOtherChargeRow(id, this.feeItemQuantities[String(id)] || 1, true));
                    this.otherChargeRows = selectedRows.length ? selectedRows : [this.createOtherChargeRow()];
                    this.syncFeeItemPayload();
                    if (this.selectedStudentId && this.selectedAcademicYearId && ! this.enrollmentSummary) {
                        this.fetchEnrollmentSummary();
                    }
                    this.$watch('selectedStudentId', () => this.fetchEnrollmentSummary());
                    this.$watch('selectedAcademicYearId', () => this.fetchEnrollmentSummary());
                    this.$watch('billingPeriodType', () => this.syncSelectedExtraRegistrations());
                    this.$watch('billingMonth', () => this.syncSelectedExtraRegistrations());
                    this.$watch('billingQuarter', () => this.syncSelectedExtraRegistrations());
                    this.$watch('customBillingLabel', () => this.syncSelectedExtraRegistrations());
                },
            };
        }
    </script>
</x-app-layout>
