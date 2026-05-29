@props([
    'exam',
    'academicYears',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Exam',
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="academic_year_id" :value="__('Academic Year')" />
            <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select Academic Year</option>
                @foreach ($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $exam->academic_year_id) === (string) $academicYear->id)>
                        {{ $academicYear->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('academic_year_id')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @php($selectedStatus = old('status', $exam->status ?: 'draft'))
                <option value="draft" @selected($selectedStatus === 'draft')>Draft</option>
                <option value="published" @selected($selectedStatus === 'published')>Published</option>
                <option value="closed" @selected($selectedStatus === 'closed')>Closed</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Exam Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $exam->name)" required placeholder="Midterm Exam" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="code" :value="__('Code')" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $exam->code)" required placeholder="MID-2026" />
            <x-input-error class="mt-2" :messages="$errors->get('code')" />
        </div>

        <div>
            <x-input-label for="term" :value="__('Term')" />
            <x-text-input id="term" name="term" type="text" class="mt-1 block w-full" :value="old('term', $exam->term)" placeholder="Term 1" />
            <x-input-error class="mt-2" :messages="$errors->get('term')" />
        </div>

        <div>
            <x-input-label for="start_date" :value="__('Start Date')" />
            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', optional($exam->start_date)->format('Y-m-d'))" />
            <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
        </div>

        <div>
            <x-input-label for="end_date" :value="__('End Date')" />
            <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date', optional($exam->end_date)->format('Y-m-d'))" />
            <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="remarks" :value="__('Remarks')" />
            <textarea id="remarks" name="remarks" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('remarks', $exam->remarks) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('remarks')" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('exams.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
