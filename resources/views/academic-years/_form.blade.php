@props([
    'academicYear',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Academic Year',
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="name" :value="__('Academic Year Name')" />
            <x-text-input
                id="name"
                name="name"
                type="text"
                class="mt-1 block w-full"
                :value="old('name', $academicYear->name)"
                required
                placeholder="2026-2027"
            />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            <select
                id="status"
                name="status"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                @php($selectedStatus = old('status', $academicYear->status ?: 'draft'))
                <option value="draft" @selected($selectedStatus === 'draft')>Draft</option>
                <option value="active" @selected($selectedStatus === 'active')>Active</option>
                <option value="closed" @selected($selectedStatus === 'closed')>Closed</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div>
            <x-input-label for="start_date" :value="__('Start Date')" />
            <x-text-input
                id="start_date"
                name="start_date"
                type="date"
                class="mt-1 block w-full"
                :value="old('start_date', optional($academicYear->start_date)->format('Y-m-d'))"
                required
            />
            <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
        </div>

        <div>
            <x-input-label for="end_date" :value="__('End Date')" />
            <x-text-input
                id="end_date"
                name="end_date"
                type="date"
                class="mt-1 block w-full"
                :value="old('end_date', optional($academicYear->end_date)->format('Y-m-d'))"
                required
            />
            <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
        </div>
    </div>

    <label class="inline-flex items-center gap-3">
        <input
            type="hidden"
            name="is_current"
            value="0"
        >
        <input
            id="is_current"
            type="checkbox"
            name="is_current"
            value="1"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
            @checked(old('is_current', $academicYear->is_current))
        >
        <span class="text-sm text-gray-700">Set as current academic year</span>
    </label>
    <x-input-error class="mt-2" :messages="$errors->get('is_current')" />

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('academic-years.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Cancel
        </a>
    </div>
</form>
