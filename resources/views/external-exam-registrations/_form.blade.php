@php
    $method = $method ?? 'POST';
    $studentOptions = $students->map(function ($student) {
        $activeEnrollment = $student->activeEnrollments->first();

        return [
            'id' => (string) $student->id,
            'label' => trim(
                $student->full_name
                . ' - ' . $student->admission_no
                . ($activeEnrollment?->academicYear ? ' - ' . $activeEnrollment->academicYear->name : '')
                . ($activeEnrollment?->grade ? ' - ' . $activeEnrollment->grade->name : '')
            ),
        ];
    })->values();
    $selectedStudent = $students->firstWhere('id', old('student_id', $registration->student_id));
    $initialStudentLabel = $selectedStudent
        ? $studentOptions->firstWhere('id', (string) $selectedStudent->id)['label'] ?? ''
        : '';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-searchable-student-select
                :selected-id="old('student_id', $registration->student_id)"
                :initial-label="$initialStudentLabel"
                :options="$studentOptions"
                helper="Type a student name or admission number, then choose from the list."
            />
        </div>

        <div>
            <x-input-label for="external_exam_session_id" :value="__('External Exam Session')" />
            <select id="external_exam_session_id" name="external_exam_session_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select external exam session</option>
                @foreach ($sessions as $session)
                    <option value="{{ $session->id }}" @selected((string) old('external_exam_session_id', request('external_exam_session_id', $registration->external_exam_session_id)) === (string) $session->id)>
                        {{ $session->name }} - {{ $session->exam_body }}{{ $session->level ? ' - ' . $session->level : '' }} ({{ $session->academicYear?->name }})
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('external_exam_session_id')" />
        </div>

        <div>
            <x-input-label for="registration_date" :value="__('Registration Date')" />
            <x-text-input id="registration_date" name="registration_date" type="date" class="mt-1 block w-full" :value="old('registration_date', optional($registration->registration_date)->format('Y-m-d') ?: $registration->registration_date)" required />
            <x-input-error class="mt-2" :messages="$errors->get('registration_date')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Registration Status')" />
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach (\App\Models\ExternalExamRegistration::statusOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $registration->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div>
            <x-input-label for="fee_amount" :value="__('Exam Fee')" />
            <x-text-input id="fee_amount" name="fee_amount" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('fee_amount', $registration->fee_amount)" required />
            <x-input-error class="mt-2" :messages="$errors->get('fee_amount')" />
        </div>

        <div>
            <x-input-label for="discount_amount" :value="__('Discount Amount')" />
            <x-text-input id="discount_amount" name="discount_amount" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('discount_amount', $registration->discount_amount)" />
            <x-input-error class="mt-2" :messages="$errors->get('discount_amount')" />
        </div>

        <div>
            <x-input-label for="candidate_no" :value="__('Candidate No')" />
            <x-text-input id="candidate_no" name="candidate_no" type="text" class="mt-1 block w-full" :value="old('candidate_no', $registration->candidate_no)" />
            <x-input-error class="mt-2" :messages="$errors->get('candidate_no')" />
        </div>

        <div>
            <x-input-label for="result_status" :value="__('Result Status')" />
            <select id="result_status" name="result_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach (\App\Models\ExternalExamRegistration::resultStatusOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('result_status', $registration->result_status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('result_status')" />
        </div>

        <div>
            <x-input-label for="score" :value="__('Score')" />
            <x-text-input id="score" name="score" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('score', $registration->score)" />
            <x-input-error class="mt-2" :messages="$errors->get('score')" />
        </div>

        <div>
            <x-input-label for="grade" :value="__('Grade / Band')" />
            <x-text-input id="grade" name="grade" type="text" class="mt-1 block w-full" :value="old('grade', $registration->grade)" />
            <x-input-error class="mt-2" :messages="$errors->get('grade')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="result_remarks" :value="__('Result Remarks')" />
            <textarea id="result_remarks" name="result_remarks" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('result_remarks', $registration->result_remarks) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('result_remarks')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="notes" :value="__('Notes')" />
            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $registration->notes) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('notes')" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('external-exam-registrations.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
