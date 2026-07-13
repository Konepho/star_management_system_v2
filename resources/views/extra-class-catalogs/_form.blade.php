@props([
    'catalog',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Extra Class',
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="name" :value="__('Class Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $catalog->name)" required placeholder="YLE Preparation" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="code" :value="__('Code')" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $catalog->code)" required placeholder="YLE-PREP" />
            <x-input-error class="mt-2" :messages="$errors->get('code')" />
        </div>

        <div>
            <x-input-label for="grade_group" :value="__('Grade Group')" />
            <select id="grade_group" name="grade_group" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All / Mixed</option>
                @foreach (\App\Models\Grade::groupOptions() as $groupValue => $groupLabel)
                    <option value="{{ $groupValue }}" @selected(old('grade_group', $catalog->grade_group) === $groupValue)>{{ $groupLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('grade_group')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            @php($selectedStatus = old('status', $catalog->status ?: 'active'))
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach (\App\Models\ExtraClassCatalog::statusOptions() as $statusValue => $statusLabel)
                    <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="description" :value="__('Description')" />
            <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $catalog->description) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('description')" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('extra-class-catalogs.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
