@props([
    'registration',
    'students',
    'academicYears',
    'offerings',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Registration',
])

@php
    $studentOptions = $students->map(function ($student) {
        $displayName = trim(collect([
            trim((string) $student->preferred_name),
            trim((string) ($student->name_en ?: $student->full_name)),
            trim((string) $student->name_mm),
        ])->filter()->unique()->implode(' / '));

        return [
            'id' => (string) $student->id,
            'label' => trim($displayName . ' - ' . $student->admission_no),
        ];
    })->values();

    $selectedStudentId = (string) old('student_id', $registration->student_id);
    $initialStudentLabel = $selectedStudentId !== ''
        ? ($studentOptions->firstWhere('id', $selectedStudentId)['label'] ?? '')
        : '';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div class="md:col-span-2">
            <x-searchable-student-select
                field-id="student_id"
                name="student_id"
                label="Student"
                :selected-id="$selectedStudentId"
                :initial-label="$initialStudentLabel"
                :options="$studentOptions"
                helper="Type a student name or admission number, then choose the student for this extra class registration."
            />
        </div>

        <div>
            <x-input-label for="academic_year_id" :value="__('Academic Year')" />
            <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select Academic Year</option>
                @foreach ($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $registration->academic_year_id) === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('academic_year_id')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="extra_class_offering_id" :value="__('Extra Class Offering')" />
            <select id="extra_class_offering_id" name="extra_class_offering_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select Offering</option>
                @foreach ($offerings as $offering)
                    <option value="{{ $offering->id }}" @selected((string) old('extra_class_offering_id', $registration->extra_class_offering_id) === (string) $offering->id)>
                        {{ $offering->academicYear?->name }} - {{ $offering->display_name }} ({{ number_format((float) $offering->fee_amount, 2) }})
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('extra_class_offering_id')" />
        </div>

        <div>
            <x-input-label for="registration_date" :value="__('Registration Date')" />
            <x-text-input id="registration_date" name="registration_date" type="date" class="mt-1 block w-full" :value="old('registration_date', optional($registration->registration_date)->format('Y-m-d') ?? $registration->registration_date)" required />
            <x-input-error class="mt-2" :messages="$errors->get('registration_date')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            @php($selectedStatus = old('status', $registration->status ?: 'active'))
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach (\App\Models\StudentExtraClassRegistration::statusOptions() as $statusValue => $statusLabel)
                    <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="remarks" :value="__('Remarks')" />
            <textarea id="remarks" name="remarks" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('remarks', $registration->remarks) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('remarks')" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('student-extra-class-registrations.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
