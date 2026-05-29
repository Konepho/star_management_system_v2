<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">POS Sales</h2>
                <p class="mt-1 text-sm text-slate-700">Review prepaid-credit sales and reverse them safely when needed.</p>
            </div>
            <a href="{{ route('pos-sales.create') }}" class="inline-flex w-full items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600 sm:w-auto">
                New POS Sale
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
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Sale No</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Owner</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Total</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Created</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($sales as $sale)
                                    <tr>
                                        <td class="px-4 py-4 font-medium text-slate-900">{{ $sale->sale_no }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <div>{{ $sale->ownerName() }}</div>
                                            <div class="text-xs text-slate-500">{{ $sale->ownerIdentifier() }}</div>
                                        </td>
                                        <td class="px-4 py-4 text-sm font-medium text-slate-900">{{ number_format((float) $sale->total_amount, 2) }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ ucfirst($sale->status) }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $sale->created_at?->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="{{ route('pos-sales.show', $sale) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">View</a>
                                                @if (auth()->user()->hasPermission('pos_sales.reverse'))
                                                    <form method="POST" action="{{ route('pos-sales.destroy', $sale) }}" onsubmit="return confirm('Reverse this POS sale?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">Reverse</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No POS sales yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
