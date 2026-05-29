<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="pos_product_category_id" :value="__('Category')" />
        <select id="pos_product_category_id" name="pos_product_category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">No category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('pos_product_category_id', $product->pos_product_category_id) === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('pos_product_category_id')" />
    </div>
    <div>
        <x-input-label for="name" :value="__('Product Name')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $product->name)" required />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>
    <div>
        <x-input-label for="sku" :value="__('SKU')" />
        <x-text-input id="sku" name="sku" type="text" class="mt-1 block w-full" :value="old('sku', $product->sku)" />
        <x-input-error class="mt-2" :messages="$errors->get('sku')" />
    </div>
    <div>
        <x-input-label for="price" :value="__('Unit Price')" />
        <x-text-input id="price" name="price" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('price', $product->price)" required />
        <x-input-error class="mt-2" :messages="$errors->get('price')" />
    </div>
    <div>
        <x-input-label for="stock_quantity" :value="__('Stock Quantity')" />
        <x-text-input id="stock_quantity" name="stock_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('stock_quantity', $product->stock_quantity ?? 0)" required />
        <x-input-error class="mt-2" :messages="$errors->get('stock_quantity')" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="description" :value="__('Description')" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $product->description) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>
    <div>
        <x-input-label for="status" :value="__('Status')" />
        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @php($selectedStatus = old('status', $product->status ?: 'active'))
            <option value="active" @selected($selectedStatus === 'active')>Active</option>
            <option value="inactive" @selected($selectedStatus === 'inactive')>Inactive</option>
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('status')" />
    </div>
</div>

<div class="mt-6 flex items-center gap-4">
    <x-primary-button>{{ $submitLabel }}</x-primary-button>
    <a href="{{ route('pos-products.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Cancel</a>
</div>
