<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">POS Reports</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-6">
                    <form method="GET" action="{{ route('reports.pos') }}" class="grid gap-4 md:grid-cols-3">
                        <div>
                            <x-input-label for="date_from" :value="__('Date From')" />
                            <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="$dateFrom" />
                        </div>
                        <div>
                            <x-input-label for="date_to" :value="__('Date To')" />
                            <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="$dateTo" />
                        </div>
                        <div class="flex items-end">
                            <x-primary-button>Apply</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="text-sm text-slate-500">Posted Sales</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($salesTotal, 2) }}</div>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="text-sm text-slate-500">Reversed Sales</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($salesReversedTotal, 2) }}</div>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="text-sm text-slate-500">Posted Top-ups</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($topupTotal, 2) }}</div>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="text-sm text-slate-500">Reversed Top-ups</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($topupReversedTotal, 2) }}</div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="overflow-hidden rounded-lg bg-white shadow-sm lg:col-span-2">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Daily Cashier Closing Summary</h3>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Cashier</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Sales</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Posted Sales</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Reversed Sales</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Top-ups</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Posted Top-ups</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Reversed Top-ups</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($cashierClosings as $closing)
                                        <tr>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ \Illuminate\Support\Carbon::parse($closing['date'])->format('d M Y') }}</td>
                                            <td class="px-4 py-4 text-sm font-medium text-slate-900">{{ $closing['cashier_name'] }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ number_format((int) $closing['sale_count']) }}</td>
                                            <td class="px-4 py-4 text-sm font-medium text-slate-900">{{ number_format((float) $closing['posted_sale_total'], 2) }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ number_format((float) $closing['reversed_sale_total'], 2) }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ number_format((int) $closing['topup_count']) }}</td>
                                            <td class="px-4 py-4 text-sm font-medium text-slate-900">{{ number_format((float) $closing['posted_topup_total'], 2) }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ number_format((float) $closing['reversed_topup_total'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">No cashier activity found in this date range.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Recent Sales</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @forelse ($recentSales as $sale)
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <div class="font-medium text-slate-900">{{ $sale->sale_no }}</div>
                                            <div class="text-sm text-slate-600">{{ $sale->ownerName() }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-semibold text-slate-900">{{ number_format((float) $sale->total_amount, 2) }}</div>
                                            <div class="text-xs text-slate-500">{{ ucfirst($sale->status) }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-slate-500">No sales found in this date range.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Recent Top-ups</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @forelse ($recentTopups as $transaction)
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <div class="font-medium text-slate-900">{{ $transaction->transaction_no ?: '#' . $transaction->id }}</div>
                                            <div class="text-sm text-slate-600">{{ $transaction->wallet?->ownerName() }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-semibold text-slate-900">{{ number_format((float) $transaction->amount, 2) }}</div>
                                            <div class="text-xs text-slate-500">{{ ucfirst($transaction->status) }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-slate-500">No top-ups found in this date range.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
