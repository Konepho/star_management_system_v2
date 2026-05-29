<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ __('Edit Discount Definition') }}</h2>
                <p class="mt-1 text-sm text-slate-700">Update this reusable school discount without changing old applied invoice records.</p>
            </div>
            <a href="{{ route('discount-definitions.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Back to Discounts
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('discount-definitions._form', [
                        'discountDefinition' => $discountDefinition,
                        'action' => route('discount-definitions.update', $discountDefinition),
                        'method' => 'PATCH',
                        'submitLabel' => 'Update Discount Definition',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
