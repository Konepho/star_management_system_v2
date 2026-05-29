@props([
    'feePlan',
    'academicYears',
    'feeStructures',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Fee Plan',
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
                    <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $feePlan->academic_year_id) === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('academic_year_id')" />
        </div>

        <div>
            <x-input-label for="grade_group" :value="__('Grade Group')" />
            <select id="grade_group" name="grade_group" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All / Mixed</option>
                @foreach (\App\Models\Grade::groupOptions() as $groupValue => $groupLabel)
                    <option value="{{ $groupValue }}" @selected(old('grade_group', $feePlan->grade_group) === $groupValue)>{{ $groupLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('grade_group')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Plan Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $feePlan->name)" required placeholder="Primary BASIC" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="code" :value="__('Plan Code')" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $feePlan->code)" required placeholder="PRIMARY-BASIC" />
            <x-input-error class="mt-2" :messages="$errors->get('code')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            @php($selectedStatus = old('status', $feePlan->status ?: 'active'))
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach (\App\Models\FeePlan::statusOptions() as $statusValue => $statusLabel)
                    <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="description" :value="__('Description')" />
            <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $feePlan->description) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('description')" />
        </div>
    </div>

    @php($selectedFeeStructures = collect(old('fee_structure_ids', $feePlan->relationLoaded('feeStructures') ? $feePlan->feeStructures->pluck('id')->all() : []))->map(fn ($id) => (string) $id)->all())
    <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
        <div class="flex flex-col gap-2">
            <h3 class="text-base font-semibold text-slate-900">Plan Fee Structures</h3>
            <p class="text-sm text-slate-600">Select the existing fee structures that belong to this named fee plan.</p>
        </div>
        <x-input-error class="mt-3" :messages="$errors->get('fee_structure_ids')" />

        <div class="mt-4 space-y-3">
            @foreach ($feeStructures as $feeStructure)
                <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
                    <input type="checkbox" name="fee_structure_ids[]" value="{{ $feeStructure->id }}" @checked(in_array((string) $feeStructure->id, $selectedFeeStructures, true)) class="mt-1 rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500">
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-slate-900">
                            {{ $feeStructure->academicYear?->name }} - {{ $feeStructure->feeCategory?->name }} - {{ $feeStructure->scope_label }}
                        </span>
                        <span class="mt-1 block text-xs text-slate-500">
                            {{ $feeStructure->billing_cycle_label }} · {{ number_format((float) $feeStructure->amount, 2) }}{{ $feeStructure->is_optional ? ' · Optional' : '' }}
                        </span>
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('fee-plans.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
