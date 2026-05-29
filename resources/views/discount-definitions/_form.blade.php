@props([
    'discountDefinition',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Discount Definition',
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="name" :value="__('Discount Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $discountDefinition->name)" required placeholder="Sibling Discount" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="code" :value="__('Discount Code')" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $discountDefinition->code)" required placeholder="SIBLING10" />
            <x-input-error class="mt-2" :messages="$errors->get('code')" />
        </div>

        <div>
            <x-input-label for="discount_type" :value="__('Discount Type')" />
            @php($selectedType = old('discount_type', $discountDefinition->discount_type ?: \App\Models\DiscountDefinition::TYPE_PERCENTAGE))
            <select id="discount_type" name="discount_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach (\App\Models\DiscountDefinition::typeOptions() as $typeValue => $typeLabel)
                    <option value="{{ $typeValue }}" @selected($selectedType === $typeValue)>{{ $typeLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('discount_type')" />
        </div>

        <div>
            <x-input-label for="value" :value="__('Discount Value')" />
            <x-text-input id="value" name="value" type="number" min="0.01" step="0.01" class="mt-1 block w-full" :value="old('value', $discountDefinition->value)" required />
            <p class="mt-1 text-xs text-slate-500">Use percent for percentage discounts, or amount for fixed discounts.</p>
            <x-input-error class="mt-2" :messages="$errors->get('value')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            @php($selectedStatus = old('status', $discountDefinition->status ?: 'active'))
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach (\App\Models\DiscountDefinition::statusOptions() as $statusValue => $statusLabel)
                    <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="description" :value="__('Description')" />
            <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $discountDefinition->description) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('description')" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('discount-definitions.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
