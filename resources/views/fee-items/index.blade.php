<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Fee Items') }}</h2>
                <p class="mt-1 text-sm text-slate-700">Manage detailed fee options like uniform sizes, book sets, and stationery packs with their own prices.</p>
            </div>
            <a href="{{ route('fee-items.create') }}" class="inline-flex w-full items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600 sm:w-auto">
                Add Fee Item
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($feeItems->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No fee items yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Add item-level fees for materials such as uniform sizes, book sets, and stationery packs.</p>
                            <div class="mt-4">
                                <a href="{{ route('fee-items.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                                    Create First Fee Item
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Category</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Item</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Code</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Variant</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Price</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Discount</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($feeItems as $feeItem)
                                        <tr>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $feeItem->feeCategory?->name }}</td>
                                            <td class="px-4 py-4 font-medium text-slate-900">
                                                {{ $feeItem->name }}
                                                @if ($feeItem->description)
                                                    <div class="text-xs text-slate-500">{{ $feeItem->description }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $feeItem->code }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $feeItem->variant ?: '—' }}</td>
                                            <td class="px-4 py-4 text-sm font-medium text-slate-900">{{ number_format((float) $feeItem->price, 2) }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $feeItem->discount_policy_label }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                    @if($feeItem->status === 'active') bg-emerald-100 text-emerald-700
                                                    @else bg-slate-200 text-slate-700 @endif">
                                                    {{ ucfirst($feeItem->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('fee-items.edit', $feeItem) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Edit</a>
                                                    <form method="POST" action="{{ route('fee-items.destroy', $feeItem) }}" onsubmit="return confirm('Deactivate this fee item?');">
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
