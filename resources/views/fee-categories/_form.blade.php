@props([
    'feeCategory',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Fee Category',
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="name" :value="__('Category Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $feeCategory->name)" required placeholder="Tuition Fee" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="code" :value="__('Category Code')" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $feeCategory->code)" required placeholder="TUITION" />
            <x-input-error class="mt-2" :messages="$errors->get('code')" />
        </div>

        <div>
            <x-input-label for="type" :value="__('Type')" />
            @php($selectedType = old('type', $feeCategory->type ?: 'mandatory'))
            <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="mandatory" @selected($selectedType === 'mandatory')>Mandatory</option>
                <option value="optional" @selected($selectedType === 'optional')>Optional</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('type')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            @php($selectedStatus = old('status', $feeCategory->status ?: 'active'))
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="active" @selected($selectedStatus === 'active')>Active</option>
                <option value="inactive" @selected($selectedStatus === 'inactive')>Inactive</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div class="md:col-span-2">
            <label for="allow_discount" class="inline-flex items-center gap-3">
                <input id="allow_discount" name="allow_discount" type="checkbox" value="1" @checked(old('allow_discount', $feeCategory->allow_discount ?? true)) class="rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500">
                <span class="text-sm font-medium text-slate-700">Allow discounts for this fee category</span>
            </label>
            <p class="mt-1 text-xs text-slate-500">Turn this off for material fees like school uniform, books, and stationery.</p>
            <x-input-error class="mt-2" :messages="$errors->get('allow_discount')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="description" :value="__('Description')" />
            <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $feeCategory->description) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('description')" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('fee-categories.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
