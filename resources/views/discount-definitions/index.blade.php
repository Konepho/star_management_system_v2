<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ __('Discount Definitions') }}</h2>
                <p class="mt-1 text-sm text-slate-700">Create reusable school discounts and scholarships before applying them to invoices.</p>
            </div>
            <a href="{{ route('discount-definitions.create') }}" class="inline-flex w-full items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600 sm:w-auto">
                Add Discount Definition
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($discountDefinitions->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No discount definitions yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Create reusable discounts like sibling support, scholarships, or early payment offers.</p>
                            <div class="mt-4">
                                <a href="{{ route('discount-definitions.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Create First Discount Definition</a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Code</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Type</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Value</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Description</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($discountDefinitions as $discountDefinition)
                                        <tr>
                                            <td class="px-4 py-4 font-medium text-slate-900">{{ $discountDefinition->name }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $discountDefinition->code }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ \App\Models\DiscountDefinition::typeOptions()[$discountDefinition->discount_type] ?? ucfirst($discountDefinition->discount_type) }}</td>
                                            <td class="px-4 py-4 text-right text-sm text-slate-600">
                                                {{ number_format((float) $discountDefinition->value, 2) }}{{ $discountDefinition->discount_type === \App\Models\DiscountDefinition::TYPE_PERCENTAGE ? '%' : '' }}
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                    @if($discountDefinition->status === 'active') bg-emerald-100 text-emerald-700
                                                    @else bg-slate-200 text-slate-700 @endif">
                                                    {{ ucfirst($discountDefinition->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $discountDefinition->description ?: '—' }}</td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('discount-definitions.edit', $discountDefinition) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Edit</a>
                                                    <form method="POST" action="{{ route('discount-definitions.destroy', $discountDefinition) }}" onsubmit="return confirm('Deactivate this discount definition?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">Deactivate</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
