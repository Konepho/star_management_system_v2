@props([
    'feeItem',
    'feeCategories',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Fee Item',
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="fee_category_id" :value="__('Fee Category')" />
            <select id="fee_category_id" name="fee_category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select Fee Category</option>
                @foreach ($feeCategories as $feeCategory)
                    <option value="{{ $feeCategory->id }}" @selected((string) old('fee_category_id', $feeItem->fee_category_id) === (string) $feeCategory->id)>
                        {{ $feeCategory->name }} ({{ $feeCategory->allow_discount ? 'Discount Allowed' : 'No Discount' }})
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('fee_category_id')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Item Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $feeItem->name)" required placeholder="School Uniform Shirt" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="code" :value="__('Item Code')" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $feeItem->code)" required placeholder="UNI-SHIRT-28" />
            <x-input-error class="mt-2" :messages="$errors->get('code')" />
        </div>

        <div>
            <x-input-label for="variant" :value="__('Variant / Size')" />
            <x-text-input id="variant" name="variant" type="text" class="mt-1 block w-full" :value="old('variant', $feeItem->variant)" placeholder="Size 28" />
            <x-input-error class="mt-2" :messages="$errors->get('variant')" />
        </div>

        <div>
            <x-input-label for="price" :value="__('Price')" />
            <x-text-input id="price" name="price" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('price', $feeItem->price)" required />
            <x-input-error class="mt-2" :messages="$errors->get('price')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            @php($selectedStatus = old('status', $feeItem->status ?: 'active'))
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="active" @selected($selectedStatus === 'active')>Active</option>
                <option value="inactive" @selected($selectedStatus === 'inactive')>Inactive</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="description" :value="__('Description')" />
            <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $feeItem->description) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('description')" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('fee-items.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
