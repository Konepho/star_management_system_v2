@props([
    'enrollment',
    'students',
    'academicYears',
    'feePlans',
    'grades',
    'sections',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Enrollment',
])

@php
    $selectedStudent = $students->firstWhere('id', old('student_id', $enrollment->student_id));
    $initialStudentLabel = $selectedStudent
        ? trim($selectedStudent->full_name . ' - ' . $selectedStudent->admission_no)
        : '';
    $studentOptions = $students->map(fn ($student) => [
        'id' => (string) $student->id,
        'label' => trim($student->full_name . ' - ' . $student->admission_no),
    ])->values();
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <x-searchable-student-select
            :selected-id="old('student_id', $enrollment->student_id ?? '')"
            :initial-label="$initialStudentLabel"
            :options="$studentOptions"
        />

        <div>
            <x-input-label for="academic_year_id" :value="__('Academic Year')" />
            <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select Academic Year</option>
                @foreach ($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $enrollment->academic_year_id) === (string) $academicYear->id)>
                        {{ $academicYear->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('academic_year_id')" />
        </div>

        <div>
            <x-input-label for="grade_id" :value="__('Grade')" />
            <select id="grade_id" name="grade_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select Grade</option>
                @foreach ($grades as $grade)
                    <option value="{{ $grade->id }}" @selected((string) old('grade_id', $enrollment->grade_id) === (string) $grade->id)>
                        {{ $grade->name }} ({{ $grade->grade_group_label }})
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('grade_id')" />
        </div>

        <div>
            <x-input-label for="fee_plan_id" :value="__('Fee Plan')" />
            <select id="fee_plan_id" name="fee_plan_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Select Fee Plan</option>
                @foreach ($feePlans as $feePlan)
                    <option value="{{ $feePlan->id }}" @selected((string) old('fee_plan_id', $enrollment->fee_plan_id) === (string) $feePlan->id)>
                        {{ $feePlan->academicYear?->name }} - {{ $feePlan->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('fee_plan_id')" />
        </div>

        <div>
            <x-input-label for="section_id" :value="__('Section')" />
            <select id="section_id" name="section_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select Section</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected((string) old('section_id', $enrollment->section_id) === (string) $section->id)>
                        {{ $section->grade->name }} - {{ $section->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('section_id')" />
        </div>

        <div>
            <x-input-label for="enrollment_date" :value="__('Enrollment Date')" />
            <x-text-input id="enrollment_date" name="enrollment_date" type="date" class="mt-1 block w-full" :value="old('enrollment_date', optional($enrollment->enrollment_date)->format('Y-m-d'))" required />
            <x-input-error class="mt-2" :messages="$errors->get('enrollment_date')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            @php($selectedStatus = old('status', $enrollment->status ?: \App\Models\Enrollment::STATUS_ACTIVE))
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach (\App\Models\Enrollment::statusOptions() as $statusValue => $statusLabel)
                    <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="remarks" :value="__('Remarks')" />
            <textarea id="remarks" name="remarks" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('remarks', $enrollment->remarks) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('remarks')" />
        </div>
    </div>

    <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
        Active enrollments update the student's current academic year, grade, and section so finance reports and class placement stay aligned.
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('enrollments.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
