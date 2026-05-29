@props([
    'mark',
    'exams',
    'students',
    'subjects',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Mark',
])

@php
    $selectedStudent = $students->firstWhere('id', old('student_id', $mark->student_id));
    $selectedEnrollment = $selectedStudent?->currentEnrollment();
    $initialStudentLabel = $selectedStudent
        ? trim($selectedStudent->admission_no . ' - ' . $selectedStudent->full_name . ' (' . ($selectedEnrollment?->academicYear?->name ?? 'No Academic Year') . ')')
        : '';
    $studentOptions = $students->map(function ($student) {
        $currentEnrollment = $student->currentEnrollment();

        return [
            'id' => (string) $student->id,
            'label' => trim($student->admission_no . ' - ' . $student->full_name . ' (' . ($currentEnrollment?->academicYear?->name ?? 'No Academic Year') . ')'),
        ];
    })->values();
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="exam_id" :value="__('Exam')" />
            <select id="exam_id" name="exam_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select Exam</option>
                @foreach ($exams as $exam)
                    <option value="{{ $exam->id }}" @selected((string) old('exam_id', $mark->exam_id) === (string) $exam->id)>
                        {{ $exam->name }} - {{ $exam->academicYear?->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('exam_id')" />
        </div>

        <x-searchable-student-select
            :selected-id="old('student_id', $mark->student_id ?? '')"
            :initial-label="$initialStudentLabel"
            :options="$studentOptions"
        />

        <div>
            <x-input-label for="subject_id" :value="__('Subject')" />
            <select id="subject_id" name="subject_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select Subject</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected((string) old('subject_id', $mark->subject_id) === (string) $subject->id)>
                        {{ $subject->name }} ({{ $subject->code }})
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('subject_id')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @php($selectedStatus = old('status', $mark->status ?: 'draft'))
                <option value="draft" @selected($selectedStatus === 'draft')>Draft</option>
                <option value="published" @selected($selectedStatus === 'published')>Published</option>
                <option value="reviewed" @selected($selectedStatus === 'reviewed')>Reviewed</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div>
            <x-input-label for="score" :value="__('Score')" />
            <x-text-input id="score" name="score" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('score', $mark->score)" required />
            <x-input-error class="mt-2" :messages="$errors->get('score')" />
        </div>

        <div>
            <x-input-label for="max_score" :value="__('Maximum Score')" />
            <x-text-input id="max_score" name="max_score" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('max_score', $mark->max_score ?: 100)" required />
            <x-input-error class="mt-2" :messages="$errors->get('max_score')" />
        </div>

        <div>
            <x-input-label for="grade_letter" :value="__('Grade Letter')" />
            <x-text-input id="grade_letter" name="grade_letter" type="text" class="mt-1 block w-full" :value="old('grade_letter', $mark->grade_letter)" placeholder="A" />
            <x-input-error class="mt-2" :messages="$errors->get('grade_letter')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="remarks" :value="__('Remarks')" />
            <textarea id="remarks" name="remarks" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('remarks', $mark->remarks) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('remarks')" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('marks.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
