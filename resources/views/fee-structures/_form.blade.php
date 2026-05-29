@props([
    'feeStructure',
    'academicYears',
    'grades',
    'feeCategories',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Fee Structure',
])

@php
    $selectedCycle = old('billing_cycle', $feeStructure->billing_cycle ?: 'monthly');
    $existingInstallments = collect(old('installments', $feeStructure->relationLoaded('installments')
        ? $feeStructure->installments->map(fn ($installment) => [
            'amount' => $installment->amount,
            'due_date' => optional($installment->due_date)->format('Y-m-d'),
            'remarks' => $installment->remarks,
        ])->all()
        : []));

    if ($selectedCycle === 'installment' && $existingInstallments->isEmpty()) {
        $existingInstallments = collect([
            ['amount' => '', 'due_date' => '', 'remarks' => ''],
        ]);
    }
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        @php
            $selectedScope = old('fee_scope');

            if ($selectedScope === null) {
                $selectedScope = $feeStructure->grade_id
                    ? 'specific'
                    : ($feeStructure->grade_group ? 'group' : 'all');
            }
        @endphp

        <div>
            <x-input-label for="academic_year_id" :value="__('Academic Year')" />
            <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select Academic Year</option>
                @foreach ($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $feeStructure->academic_year_id) === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('academic_year_id')" />
        </div>

        <div>
            <x-input-label for="fee_scope" :value="__('Fee Applies To')" />
            <select id="fee_scope" name="fee_scope" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="all" @selected($selectedScope === 'all')>All Grades / School-Wide</option>
                <option value="group" @selected($selectedScope === 'group')>Primary or Secondary Group</option>
                <option value="specific" @selected($selectedScope === 'specific')>One Specific Grade</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('fee_scope')" />
            <p class="mt-1 text-xs text-slate-500">Use group fees when the same amount should apply to all Primary grades or all Secondary grades.</p>
        </div>

        <div>
            <x-input-label for="grade_group" :value="__('Grade Group')" />
            <select id="grade_group" name="grade_group" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Select Group</option>
                @foreach (\App\Models\Grade::groupOptions() as $groupValue => $groupLabel)
                    <option value="{{ $groupValue }}" @selected(old('grade_group', $feeStructure->grade_group) === $groupValue)>{{ $groupLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('grade_group')" />
        </div>

        <div>
            <x-input-label for="grade_id" :value="__('Specific Grade')" />
            <select id="grade_id" name="grade_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Select Grade</option>
                @foreach ($grades as $grade)
                    <option value="{{ $grade->id }}" @selected((string) old('grade_id', $feeStructure->grade_id) === (string) $grade->id)>{{ $grade->name }} ({{ $grade->grade_group_label }})</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('grade_id')" />
        </div>

        <div>
            <x-input-label for="fee_category_id" :value="__('Fee Category')" />
            <select id="fee_category_id" name="fee_category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select Fee Category</option>
                @foreach ($feeCategories as $feeCategory)
                    <option value="{{ $feeCategory->id }}" @selected((string) old('fee_category_id', $feeStructure->fee_category_id) === (string) $feeCategory->id)>{{ $feeCategory->name }} ({{ $feeCategory->code }})</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('fee_category_id')" />
        </div>

        <div>
            <x-input-label for="amount" :value="__('Total Amount')" />
            <x-text-input id="amount" name="amount" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('amount', $feeStructure->amount)" />
            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
            <p class="mt-1 text-xs text-slate-500">For installment plans, this total is calculated from the installment lines below.</p>
        </div>

        <div>
            <x-input-label for="billing_cycle" :value="__('Billing Cycle')" />
            <select id="billing_cycle" name="billing_cycle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach (\App\Models\FeeStructure::billingCycleOptions() as $cycleValue => $cycleLabel)
                    <option value="{{ $cycleValue }}" @selected($selectedCycle === $cycleValue)>{{ $cycleLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('billing_cycle')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            @php($selectedStatus = old('status', $feeStructure->status ?: 'active'))
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="active" @selected($selectedStatus === 'active')>Active</option>
                <option value="inactive" @selected($selectedStatus === 'inactive')>Inactive</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div class="md:col-span-2">
            <label for="is_optional" class="inline-flex items-center gap-3">
                <input id="is_optional" name="is_optional" type="checkbox" value="1" @checked(old('is_optional', $feeStructure->is_optional)) class="rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500">
                <span class="text-sm font-medium text-slate-700">This fee is optional</span>
            </label>
            <x-input-error class="mt-2" :messages="$errors->get('is_optional')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="remarks" :value="__('Remarks')" />
            <textarea id="remarks" name="remarks" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('remarks', $feeStructure->remarks) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('remarks')" />
        </div>
    </div>

    <div id="installment-panel" class="space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-5" @if($selectedCycle !== 'installment') style="display: none;" @endif>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Installment Plan</h3>
                <p class="mt-1 text-sm text-slate-600">Add one row for each payment. Example: `2 x 1,350,000` for Foundation or `3 x 2,750,000` for IGCSE.</p>
            </div>
            <button type="button" id="add-installment" class="inline-flex items-center rounded-md border border-sky-300 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-sky-50">
                Add Installment
            </button>
        </div>

        <x-input-error class="mt-2" :messages="$errors->get('installments')" />

        <div id="installment-rows" class="space-y-4">
            @foreach ($existingInstallments as $index => $installment)
                <div class="installment-row grid gap-4 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-3">
                    <div>
                        <x-input-label :for="'installments_'.$index.'_amount'" :value="'Installment '.($index + 1).' Amount'" />
                        <x-text-input :id="'installments_'.$index.'_amount'" :name="'installments['.$index.'][amount]'" type="number" min="0" step="0.01" class="mt-1 block w-full installment-amount" :value="data_get($installment, 'amount')" />
                        <x-input-error class="mt-2" :messages="$errors->get('installments.'.$index.'.amount')" />
                    </div>

                    <div>
                        <x-input-label :for="'installments_'.$index.'_due_date'" :value="'Due Date'" />
                        <x-text-input :id="'installments_'.$index.'_due_date'" :name="'installments['.$index.'][due_date]'" type="date" class="mt-1 block w-full" :value="data_get($installment, 'due_date')" />
                        <x-input-error class="mt-2" :messages="$errors->get('installments.'.$index.'.due_date')" />
                    </div>

                    <div class="flex flex-col">
                        <div class="flex items-center justify-between gap-3">
                            <x-input-label :for="'installments_'.$index.'_remarks'" :value="'Remarks'" />
                            <button type="button" class="remove-installment text-sm font-medium text-rose-700 hover:text-rose-600">Remove</button>
                        </div>
                        <x-text-input :id="'installments_'.$index.'_remarks'" :name="'installments['.$index.'][remarks]'" type="text" class="mt-1 block w-full" :value="data_get($installment, 'remarks')" />
                        <x-input-error class="mt-2" :messages="$errors->get('installments.'.$index.'.remarks')" />
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('fee-structures.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>

<template id="installment-row-template">
    <div class="installment-row grid gap-4 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 installment-amount-label"></label>
            <input type="number" min="0" step="0.01" class="installment-amount mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 installment-due-date-label">Due Date</label>
            <input type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
        </div>

        <div class="flex flex-col">
            <div class="flex items-center justify-between gap-3">
                <label class="block text-sm font-medium text-gray-700 installment-remarks-label">Remarks</label>
                <button type="button" class="remove-installment text-sm font-medium text-rose-700 hover:text-rose-600">Remove</button>
            </div>
            <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
        </div>
    </div>
</template>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const billingCycle = document.getElementById('billing_cycle');
        const amountInput = document.getElementById('amount');
        const panel = document.getElementById('installment-panel');
        const rows = document.getElementById('installment-rows');
        const addButton = document.getElementById('add-installment');
        const template = document.getElementById('installment-row-template');

        const toggleInstallmentPanel = () => {
            const active = billingCycle.value === 'installment';
            panel.style.display = active ? '' : 'none';
            amountInput.readOnly = active;

            if (active && rows.children.length === 0) {
                addInstallmentRow();
            }

            if (active) {
                recalculateTotal();
            }
        };

        const recalculateTotal = () => {
            if (billingCycle.value !== 'installment') {
                return;
            }

            const total = Array.from(rows.querySelectorAll('.installment-amount'))
                .reduce((sum, input) => sum + (parseFloat(input.value || '0') || 0), 0);

            amountInput.value = total ? total.toFixed(2) : '';
        };

        const reindexRows = () => {
            Array.from(rows.children).forEach((row, index) => {
                const amountLabel = row.querySelector('.installment-amount-label') ?? row.querySelector('label[for*="_amount"]');
                const amountInputField = row.querySelector('.installment-amount');
                const dueDateLabel = row.querySelector('.installment-due-date-label') ?? row.querySelector('label[for*="_due_date"]');
                const dueDateInputField = row.querySelector('input[type="date"]');
                const remarksLabel = row.querySelector('.installment-remarks-label') ?? row.querySelector('label[for*="_remarks"]');
                const remarksInputField = row.querySelector('input[type="text"]');

                const amountId = `installments_${index}_amount`;
                const dueDateId = `installments_${index}_due_date`;
                const remarksId = `installments_${index}_remarks`;

                amountLabel.textContent = `Installment ${index + 1} Amount`;
                amountLabel.setAttribute('for', amountId);
                amountInputField.id = amountId;
                amountInputField.name = `installments[${index}][amount]`;

                dueDateLabel.setAttribute('for', dueDateId);
                dueDateInputField.id = dueDateId;
                dueDateInputField.name = `installments[${index}][due_date]`;

                remarksLabel.setAttribute('for', remarksId);
                remarksInputField.id = remarksId;
                remarksInputField.name = `installments[${index}][remarks]`;
            });
        };

        const addInstallmentRow = (values = {}) => {
            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('.installment-row');

            row.querySelector('.installment-amount').value = values.amount ?? '';
            row.querySelector('input[type="date"]').value = values.due_date ?? '';
            row.querySelector('input[type="text"]').value = values.remarks ?? '';

            rows.appendChild(row);
            reindexRows();
            recalculateTotal();
        };

        rows.addEventListener('input', (event) => {
            if (event.target.classList.contains('installment-amount')) {
                recalculateTotal();
            }
        });

        rows.addEventListener('click', (event) => {
            if (!event.target.classList.contains('remove-installment')) {
                return;
            }

            event.preventDefault();
            event.target.closest('.installment-row')?.remove();
            reindexRows();
            recalculateTotal();
        });

        addButton.addEventListener('click', () => addInstallmentRow());
        billingCycle.addEventListener('change', toggleInstallmentPanel);

        reindexRows();
        toggleInstallmentPanel();
    });
</script>
