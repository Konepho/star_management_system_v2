@props([
    'grade',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Grade',
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="name" :value="__('Grade Name')" />
            <x-text-input
                id="name"
                name="name"
                type="text"
                class="mt-1 block w-full"
                :value="old('name', $grade->name)"
                required
                placeholder="Grade 1"
            />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="code" :value="__('Code')" />
            <x-text-input
                id="code"
                name="code"
                type="text"
                class="mt-1 block w-full"
                :value="old('code', $grade->code)"
                required
                placeholder="G1"
            />
            <x-input-error class="mt-2" :messages="$errors->get('code')" />
        </div>

        <div>
            <x-input-label for="grade_group" :value="__('Grade Group')" />
            @php($selectedGroup = old('grade_group', $grade->grade_group))
            <select
                id="grade_group"
                name="grade_group"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                <option value="">Select Group</option>
                @foreach (\App\Models\Grade::groupOptions() as $groupValue => $groupLabel)
                    <option value="{{ $groupValue }}" @selected($selectedGroup === $groupValue)>{{ $groupLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('grade_group')" />
        </div>

        <div>
            <x-input-label for="sort_order" :value="__('Sort Order')" />
            <x-text-input
                id="sort_order"
                name="sort_order"
                type="number"
                min="0"
                class="mt-1 block w-full"
                :value="old('sort_order', $grade->sort_order ?? 0)"
                required
            />
            <x-input-error class="mt-2" :messages="$errors->get('sort_order')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="remarks" :value="__('Remarks')" />
            <textarea
                id="remarks"
                name="remarks"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Optional notes about this grade"
            >{{ old('remarks', $grade->remarks) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('remarks')" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('grades.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Cancel
        </a>
    </div>
</form>
