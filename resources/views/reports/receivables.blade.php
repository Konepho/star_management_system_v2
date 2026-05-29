<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Receivables Report') }}</h2>
                <p class="mt-1 text-sm text-slate-700">See invoices that still need payment and the amount expected by month or year.</p>
            </div>
            <a href="{{ route('student-invoices.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Open Invoices
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 shadow-xl">
                <div class="grid gap-8 px-6 py-8 text-white lg:grid-cols-[1.2fr_0.8fr] lg:px-8">
                    <div>
                        <div class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-sky-100">
                            Finance Overview
                        </div>
                        <h3 class="mt-4 text-3xl font-semibold tracking-tight">Track incoming payments like a finance dashboard.</h3>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200">
                            Monitor unpaid invoices, compare expected vs collected amounts, and jump straight into payment collection for balances due.
                        </p>

                        <div class="mt-6 grid gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-100">Selected Period</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $currentPeriodLabel }}</div>
                                <div class="mt-1 text-xs text-slate-300">{{ $viewMode === 'yearly' ? 'Yearly receivables view' : 'Monthly receivables view' }}</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-100">Expected</div>
                                <div class="mt-2 text-2xl font-semibold">{{ number_format($expectedForPeriod, 2) }}</div>
                                <div class="mt-1 text-xs text-slate-300">Amount scheduled in this period</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-100">Collected</div>
                                <div class="mt-2 text-2xl font-semibold">{{ number_format($collectedForPeriod, 2) }}</div>
                                <div class="mt-1 text-xs text-slate-300">{{ number_format($collectionRate, 1) }}% of expected amount</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur">
                        <div>
                            <div class="text-sm font-semibold text-white">Report Filters</div>
                            <div class="mt-1 text-xs text-slate-300">Adjust the period to inspect outstanding receivables.</div>
                        </div>

                        <form method="GET" action="{{ route('reports.receivables') }}" class="mt-5 grid gap-4">
                            <div>
                                <x-input-label for="view" :value="__('View')" class="!text-slate-200" />
                                <select id="view" name="view" class="mt-1 block w-full rounded-xl border-white/10 bg-slate-950/40 text-white shadow-sm focus:border-sky-400 focus:ring-sky-400">
                                    <option value="monthly" @selected($viewMode === 'monthly')>Monthly</option>
                                    <option value="yearly" @selected($viewMode === 'yearly')>Yearly</option>
                                </select>
                            </div>

                            <div>
                                <x-input-label for="year" :value="__('Year')" class="!text-slate-200" />
                                <select id="year" name="year" class="mt-1 block w-full rounded-xl border-white/10 bg-slate-950/40 text-white shadow-sm focus:border-sky-400 focus:ring-sky-400">
                                    @foreach ($availableYears as $year)
                                        <option value="{{ $year }}" @selected($selectedYear === (int) $year)>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="month" :value="__('Month')" class="!text-slate-200" />
                                <select id="month" name="month" class="mt-1 block w-full rounded-xl border-white/10 bg-slate-950/40 text-white shadow-sm focus:border-sky-400 focus:ring-sky-400" @disabled($viewMode === 'yearly')>
                                    @foreach (range(1, 12) as $month)
                                        <option value="{{ $month }}" @selected($selectedMonth === $month)>{{ now()->startOfYear()->month($month)->format('F') }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600">
                                Apply Filter
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Outstanding</div>
                        <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ number_format($totalOutstanding, 2) }}</div>
                        <div class="mt-2 text-sm text-slate-500">Unpaid balances across open invoices</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $viewMode === 'yearly' ? 'Year Outstanding' : 'Month Outstanding' }}</div>
                        <div class="mt-3 text-3xl font-semibold tracking-tight text-rose-700">{{ number_format($periodOutstanding, 2) }}</div>
                        <div class="mt-2 text-sm text-slate-500">Still unpaid inside {{ $currentPeriodLabel }}</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Overdue</div>
                        <div class="mt-3 text-3xl font-semibold tracking-tight text-amber-700">{{ number_format($overdueAmount, 2) }}</div>
                        <div class="mt-2 text-sm text-slate-500">{{ $overdueInvoices->count() }} invoice(s) past due date</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Collection Gap</div>
                        <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ number_format($collectionGap, 2) }}</div>
                        <div class="mt-2 text-sm text-slate-500">Expected minus collected for {{ $currentPeriodLabel }}</div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.7fr_1fr]">
                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">
                                    {{ $viewMode === 'yearly' ? 'Invoices Due in Selected Year' : 'Invoices Due in Selected Month' }}
                                </h3>
                                <p class="mt-1 text-sm text-slate-500">Use this list to see who still needs to pay and jump directly into payment collection.</p>
                            </div>
                            <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">
                                {{ $dueInvoices->count() }} Open Invoice{{ $dueInvoices->count() === 1 ? '' : 's' }}
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Customer</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Invoice</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Due Date</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Status</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Amount Due</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($dueInvoices as $invoice)
                                    @php($isOverdue = $invoice->due_date && $invoice->due_date->isPast())
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="px-6 py-5 text-sm text-slate-600">
                                            <div class="font-semibold text-slate-900">{{ $invoice->student?->full_name }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ $invoice->student?->admission_no }}{{ $invoice->student?->grade ? ' • '.$invoice->student->grade->name : '' }}</div>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-slate-600">
                                            <div class="font-semibold text-slate-900">{{ $invoice->invoice_no }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ $invoice->academicYear?->name }}</div>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-slate-600">
                                            <div>{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</div>
                                            @if ($isOverdue)
                                                <div class="mt-1 text-xs font-medium text-amber-700">Overdue</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-5 text-sm">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                @if($invoice->status === 'partial') bg-amber-100 text-amber-700
                                                @elseif($invoice->status === 'issued') bg-sky-100 text-sky-700
                                                @else bg-slate-200 text-slate-700 @endif">
                                                {{ ucfirst($invoice->status) }}
                                            </span>
                                            @if ($invoice->payment_timing_status_label)
                                                <div class="mt-2 text-xs text-slate-500">{{ $invoice->payment_timing_status_label }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-5 text-right text-sm font-semibold {{ $isOverdue ? 'text-amber-700' : 'text-slate-900' }}">
                                            {{ number_format((float) $invoice->balance_due, 2) }}
                                        </td>
                                        <td class="px-6 py-5 text-right text-sm">
                                            <a href="{{ route('student-invoices.show', $invoice) }}#collect-payment" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500">
                                                Receive Payment
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">No unpaid invoices found for this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h3 class="text-lg font-semibold text-slate-900">Expected by Month</h3>
                            <p class="mt-1 text-sm text-slate-500">Outstanding amounts grouped by invoice due month.</p>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                @forelse ($expectedByMonth as $row)
                                    <div class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200 p-4">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">{{ $row['label'] }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ $row['count'] }} invoice(s)</div>
                                        </div>
                                        <div class="text-right text-sm font-semibold text-slate-900">{{ number_format((float) $row['amount'], 2) }}</div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">No expected monthly receivables yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h3 class="text-lg font-semibold text-slate-900">Expected by Year</h3>
                            <p class="mt-1 text-sm text-slate-500">Outstanding amounts grouped by invoice due year.</p>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                @forelse ($expectedByYear as $row)
                                    <div class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200 p-4">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">{{ $row['label'] }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ $row['count'] }} invoice(s)</div>
                                        </div>
                                        <div class="text-right text-sm font-semibold text-slate-900">{{ number_format((float) $row['amount'], 2) }}</div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">No expected yearly receivables yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h3 class="text-lg font-semibold text-slate-900">Collection Snapshot</h3>
                            <p class="mt-1 text-sm text-slate-500">Quick comparison for the selected period.</p>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Collected This Month</div>
                                    <div class="mt-2 text-lg font-semibold text-emerald-700">{{ number_format($paymentsForMonth, 2) }}</div>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Collected This Year</div>
                                    <div class="mt-2 text-lg font-semibold text-emerald-700">{{ number_format($paymentsForYear, 2) }}</div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-slate-600">Expected in {{ $currentPeriodLabel }}</span>
                                <span class="text-sm font-semibold text-slate-900">{{ number_format($expectedForPeriod, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-slate-600">Collected in {{ $currentPeriodLabel }}</span>
                                <span class="text-sm font-semibold text-emerald-700">{{ number_format($collectedForPeriod, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-slate-600">Gap</span>
                                <span class="text-sm font-semibold text-rose-700">{{ number_format($collectionGap, 2) }}</span>
                            </div>
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-4 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                    <span>Collection Rate</span>
                                    <span>{{ number_format($collectionRate, 1) }}%</span>
                                </div>
                                <div class="h-2.5 rounded-full bg-slate-100">
                                    <div class="h-2.5 rounded-full bg-sky-500" style="width: {{ min(100, $collectionRate) }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
