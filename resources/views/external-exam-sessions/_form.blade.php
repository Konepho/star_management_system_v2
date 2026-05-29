@php($method = $method ?? 'POST')

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="academic_year_id" :value="__('Academic Year')" />
            <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select academic year</option>
                @foreach ($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $session->academic_year_id) === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('academic_year_id')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach (\App\Models\ExternalExamSession::statusOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $session->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Session Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $session->name)" required />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="exam_body" :value="__('Exam Body')" />
            <x-text-input id="exam_body" name="exam_body" type="text" class="mt-1 block w-full" :value="old('exam_body', $session->exam_body)" required />
            <x-input-error class="mt-2" :messages="$errors->get('exam_body')" />
        </div>

        <div>
            <x-input-label for="level" :value="__('Level / Paper')" />
            <x-text-input id="level" name="level" type="text" class="mt-1 block w-full" :value="old('level', $session->level)" />
            <x-input-error class="mt-2" :messages="$errors->get('level')" />
        </div>

        <div>
            <x-input-label for="fee_amount" :value="__('Default Fee')" />
            <x-text-input id="fee_amount" name="fee_amount" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('fee_amount', $session->fee_amount)" required />
            <x-input-error class="mt-2" :messages="$errors->get('fee_amount')" />
        </div>

        <div>
            <x-input-label for="registration_deadline" :value="__('Registration Deadline')" />
            <x-text-input id="registration_deadline" name="registration_deadline" type="date" class="mt-1 block w-full" :value="old('registration_deadline', optional($session->registration_deadline)->format('Y-m-d'))" />
            <x-input-error class="mt-2" :messages="$errors->get('registration_deadline')" />
        </div>

        <div>
            <x-input-label for="exam_date" :value="__('Exam Date')" />
            <x-text-input id="exam_date" name="exam_date" type="date" class="mt-1 block w-full" :value="old('exam_date', optional($session->exam_date)->format('Y-m-d'))" />
            <x-input-error class="mt-2" :messages="$errors->get('exam_date')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="remarks" :value="__('Remarks')" />
            <textarea id="remarks" name="remarks" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('remarks', $session->remarks) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('remarks')" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('external-exam-sessions.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
