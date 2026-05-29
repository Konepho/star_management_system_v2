<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Edit Fee Item') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('fee-items._form', [
                        'feeItem' => $feeItem,
                        'feeCategories' => $feeCategories,
                        'action' => route('fee-items.update', $feeItem),
                        'method' => 'PATCH',
                        'submitLabel' => 'Update Fee Item',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
