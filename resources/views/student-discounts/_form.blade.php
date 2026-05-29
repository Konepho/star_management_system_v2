@props([
    'studentDiscount',
    'students',
    'discountDefinitions',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Student Discount',
])

@php
    $selectedStudent = $students->firstWhere('id', old('student_id', $studentDiscount->student_id));
    $initialStudentLabel = $selectedStudent
        ? trim($selectedStudent->full_name . ' - ' . $selectedStudent->admission_no . ($selectedStudent->grade ? ' - ' . $selectedStudent->grade->name : ''))
        : '';
    $studentOptions = $students->map(fn ($student) => [
        'id' => (string) $student->id,
        'label' => trim($student->full_name . ' - ' . $student->admission_no . ($student->grade ? ' - ' . $student->grade->name : '')),
    ])->values();
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <x-searchable-student-select
            :selected-id="old('student_id', $studentDiscount->student_id ?? '')"
            :initial-label="$initialStudentLabel"
            :options="$studentOptions"
        />

        <div>
            <x-input-label for="discount_definition_id" :value="__('Discount Definition')" />
            <select id="discount_definition_id" name="discount_definition_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select Discount</option>
                @foreach ($discountDefinitions as $discountDefinition)
                    <option value="{{ $discountDefinition->id }}" @selected((string) old('discount_definition_id', $studentDiscount->discount_definition_id) === (string) $discountDefinition->id)>
                        {{ $discountDefinition->name }} - {{ \App\Models\DiscountDefinition::typeOptions()[$discountDefinition->discount_type] ?? ucfirst($discountDefinition->discount_type) }} ({{ number_format((float) $discountDefinition->value, 2) }}{{ $discountDefinition->discount_type === \App\Models\DiscountDefinition::TYPE_PERCENTAGE ? '%' : '' }})
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('discount_definition_id')" />
        </div>

        <div>
            <x-input-label for="start_date" :value="__('Start Date')" />
            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', optional($studentDiscount->start_date)->format('Y-m-d') ?? $studentDiscount->start_date)" required />
            <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
        </div>

        <div>
            <x-input-label for="end_date" :value="__('End Date')" />
            <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date', optional($studentDiscount->end_date)->format('Y-m-d') ?? $studentDiscount->end_date)" />
            <p class="mt-1 text-xs text-slate-500">Leave blank if this scholarship or discount continues until you stop it.</p>
            <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            @php($selectedStatus = old('status', $studentDiscount->status ?: 'active'))
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach (\App\Models\StudentDiscount::statusOptions() as $statusValue => $statusLabel)
                    <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="notes" :value="__('Notes')" />
            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $studentDiscount->notes) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('notes')" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('student-discounts.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
