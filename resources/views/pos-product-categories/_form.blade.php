<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="name" :value="__('Category Name')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $category->name)" required />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>
    <div>
        <x-input-label for="code" :value="__('Code')" />
        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $category->code)" />
        <x-input-error class="mt-2" :messages="$errors->get('code')" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="description" :value="__('Description')" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $category->description) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>
    <div>
        <x-input-label for="status" :value="__('Status')" />
        @php($selectedStatus = old('status', $category->status ?: 'active'))
        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="active" @selected($selectedStatus === 'active')>Active</option>
            <option value="inactive" @selected($selectedStatus === 'inactive')>Inactive</option>
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('status')" />
    </div>
</div>

<div class="mt-6 flex items-center gap-4">
    <x-primary-button>{{ $submitLabel }}</x-primary-button>
    <a href="{{ route('pos-product-categories.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Cancel</a>
</div>
