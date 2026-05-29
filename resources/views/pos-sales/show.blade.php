<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">POS Sale {{ $sale->sale_no }}</h2>
                <p class="mt-1 text-sm text-slate-700">{{ $sale->ownerName() }} · {{ $sale->ownerIdentifier() }}</p>
            </div>
            @if (auth()->user()->hasPermission('pos_sales.reverse') && ! $sale->isReversed())
                <form method="POST" action="{{ route('pos-sales.destroy', $sale) }}" onsubmit="return confirm('Reverse this POS sale?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500">
                        Reverse Sale
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="text-sm font-semibold uppercase tracking-wide text-slate-500">Sale Summary</div>
                    <dl class="mt-4 space-y-3">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Total</dt>
                            <dd class="text-lg font-semibold text-slate-900">{{ number_format((float) $sale->total_amount, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Status</dt>
                            <dd class="text-sm text-slate-900">{{ ucfirst($sale->status) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Balance Change</dt>
                            <dd class="text-sm text-slate-900">{{ number_format((float) $sale->balance_before, 2) }} → {{ number_format((float) $sale->balance_after, 2) }}</dd>
                        </div>
                    </dl>
                </div>
                <div class="overflow-hidden rounded-lg bg-white shadow-sm lg:col-span-2">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-slate-900">Items</h3>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Product</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Qty</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Unit Price</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($sale->items as $item)
                                        <tr>
                                            <td class="px-4 py-4 text-sm font-medium text-slate-900">{{ $item->product_name }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $item->quantity }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ number_format((float) $item->unit_price, 2) }}</td>
                                            <td class="px-4 py-4 text-sm font-medium text-slate-900">{{ number_format((float) $item->line_total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
