@props([
    'offering',
    'catalogs',
    'academicYears',
    'feeCategories',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Offering',
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="extra_class_catalog_id" :value="__('Extra Class')" />
            <select id="extra_class_catalog_id" name="extra_class_catalog_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select Extra Class</option>
                @foreach ($catalogs as $catalog)
                    <option value="{{ $catalog->id }}" @selected((string) old('extra_class_catalog_id', $offering->extra_class_catalog_id) === (string) $catalog->id)>{{ $catalog->name }} ({{ $catalog->code }})</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('extra_class_catalog_id')" />
        </div>

        <div>
            <x-input-label for="academic_year_id" :value="__('Academic Year')" />
            <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select Academic Year</option>
                @foreach ($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $offering->academic_year_id) === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('academic_year_id')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Offering Name Override')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $offering->name)" placeholder="Optional custom label for this year" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="fee_category_id" :value="__('Fee Category')" />
            <select id="fee_category_id" name="fee_category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Uncategorized</option>
                @foreach ($feeCategories as $feeCategory)
                    <option value="{{ $feeCategory->id }}" @selected((string) old('fee_category_id', $offering->fee_category_id) === (string) $feeCategory->id)>{{ $feeCategory->name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('fee_category_id')" />
        </div>

        <div>
            <x-input-label for="fee_amount" :value="__('Fee Amount')" />
            <x-text-input id="fee_amount" name="fee_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('fee_amount', $offering->fee_amount)" required />
            <x-input-error class="mt-2" :messages="$errors->get('fee_amount')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            @php($selectedStatus = old('status', $offering->status ?: 'active'))
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach (\App\Models\ExtraClassOffering::statusOptions() as $statusValue => $statusLabel)
                    <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div>
            <x-input-label for="start_date" :value="__('Start Date')" />
            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', optional($offering->start_date)->format('Y-m-d') ?? $offering->start_date)" />
            <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
        </div>

        <div>
            <x-input-label for="end_date" :value="__('End Date')" />
            <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date', optional($offering->end_date)->format('Y-m-d') ?? $offering->end_date)" />
            <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="remarks" :value="__('Remarks')" />
            <textarea id="remarks" name="remarks" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('remarks', $offering->remarks) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('remarks')" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('extra-class-offerings.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
