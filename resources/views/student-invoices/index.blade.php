<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Student Invoices') }}</h2>
                <p class="mt-1 text-sm text-slate-700">Generate invoices from enrollments and fee plans for each billing period.</p>
            </div>
            <a href="{{ route('student-invoices.create') }}" class="inline-flex w-full items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600 sm:w-auto">
                Generate Invoice
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto w-full max-w-none space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('student-invoices.index') }}" class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                            <div class="flex-1">
                                <label for="search" class="text-sm font-semibold text-slate-800">Search Invoices</label>
                                <input
                                    id="search"
                                    name="search"
                                    type="text"
                                    value="{{ $search }}"
                                    placeholder="Search by invoice no, student name, admission no, or billing period"
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                >
                            </div>
                            <div class="flex gap-3">
                                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600">
                                    Search
                                </button>
                                @if ($search !== '')
                                    <a href="{{ route('student-invoices.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-white">
                                        Clear
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    @if ($invoices->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $search !== '' ? 'No matching invoices found' : 'No invoices yet' }}</h3>
                            <p class="mt-2 text-sm text-slate-500">
                                {{ $search !== '' ? 'Try a different invoice number, student name, admission number, or billing period.' : 'Generate fee invoices for students once your fee structures are ready.' }}
                            </p>
                            <div class="mt-4">
                                @if ($search !== '')
                                    <a href="{{ route('student-invoices.index') }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-white">
                                        Clear Search
                                    </a>
                                @else
                                    <a href="{{ route('student-invoices.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                                        Create First Invoice
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <table class="min-w-full table-fixed divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="w-[13%] px-3 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Invoice</th>
                                        <th class="w-[19%] px-3 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Student</th>
                                        <th class="w-[17%] px-3 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Billing</th>
                                        <th class="w-[13%] px-3 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Totals</th>
                                        <th class="w-[13%] px-3 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Collection</th>
                                        <th class="w-[11%] px-3 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="w-[14%] px-3 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($invoices as $invoice)
                                        <tr class="align-top">
                                            <td class="px-3 py-3">
                                                <div class="break-words text-sm font-semibold text-slate-900">{{ $invoice->invoice_no }}</div>
                                                <div class="mt-1 text-xs text-slate-500">{{ $invoice->issue_date?->format('Y-m-d') }}</div>
                                            </td>
                                            <td class="px-3 py-3">
                                                <div class="break-words text-sm font-medium text-slate-900">{{ $invoice->student?->full_name }}</div>
                                                <div class="mt-1 break-words text-xs text-slate-500">
                                                    {{ $invoice->student?->admission_no ?: '—' }}
                                                    @if ($invoice->grade)
                                                        • {{ $invoice->grade->name }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-3 py-3 text-sm text-slate-600">
                                                <div class="break-words font-medium text-slate-800">{{ $invoice->academicYear?->name }}</div>
                                                <div class="mt-1 break-words text-xs text-slate-600">{{ $invoice->billing_period_label }}</div>
                                                <div class="mt-1 text-xs text-slate-500">{{ \App\Models\StudentInvoice::billingPeriodTypeOptions()[$invoice->billing_period_type] ?? ucfirst((string) $invoice->billing_period_type) }}</div>
                                            </td>
                                            <td class="px-3 py-3 text-xs text-slate-600">
                                                <div>Sub: <span class="font-medium text-slate-900">{{ number_format((float) $invoice->subtotal_amount, 2) }}</span></div>
                                                <div class="mt-1 text-emerald-700">Disc: {{ number_format((float) $invoice->discount_amount, 2) }}</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ number_format((float) $invoice->total_amount, 2) }}</div>
                                            </td>
                                            <td class="px-3 py-3 text-xs text-slate-600">
                                                <div>Paid: <span class="font-medium text-slate-900">{{ number_format((float) $invoice->paid_amount, 2) }}</span></div>
                                                <div class="mt-1">Bal: <span class="font-medium text-slate-900">{{ number_format((float) $invoice->balance_due, 2) }}</span></div>
                                                @if ($invoice->discounts->where('is_auto_applied', true)->isNotEmpty())
                                                    <div class="mt-1 text-emerald-600">Auto discount</div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold
                                                    @if($invoice->status === 'paid') bg-emerald-100 text-emerald-700
                                                    @elseif($invoice->status === 'partial') bg-amber-100 text-amber-700
                                                    @elseif($invoice->status === 'issued') bg-sky-100 text-sky-700
                                                    @elseif($invoice->status === 'draft') bg-slate-200 text-slate-700
                                                    @else bg-rose-100 text-rose-700 @endif">
                                                    {{ ucfirst($invoice->status) }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3">
                                                <div class="flex flex-col items-end gap-2 text-right">
                                                    <a href="{{ route('student-invoices.show', $invoice) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">View</a>
                                                    <a href="{{ route('student-invoices.print', $invoice) }}" target="_blank" rel="noopener" class="text-sm font-medium text-slate-700 hover:text-slate-900">Print</a>
                                                    @if ($invoice->balance_due > 0 && in_array($invoice->status, ['issued', 'partial'], true))
                                                        <a href="{{ route('student-invoices.show', $invoice) }}#collect-payment" class="text-sm font-medium text-emerald-700 hover:text-emerald-600">Make Payment</a>
                                                    @elseif ($invoice->status === 'paid')
                                                        <span class="text-sm font-medium text-emerald-700">Completed</span>
                                                    @endif
                                                    @if ($invoice->status === \App\Models\StudentInvoice::STATUS_DRAFT)
                                                        <form method="POST" action="{{ route('student-invoices.update-status', $invoice) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="action" value="issue">
                                                            <button type="submit" class="text-sm font-medium text-emerald-700 hover:text-emerald-600">Issue</button>
                                                        </form>
                                                    @endif
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
