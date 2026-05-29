<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Payment Collection') }}</h2>
                <p class="mt-1 text-sm text-slate-700">Track student payments, methods, receipts, and invoice balances.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($payments->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No payments yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Collect payments from the invoice detail page once invoices are generated.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Receipt No</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Student</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Invoice</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Method</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($payments as $payment)
                                        <tr>
                                            <td class="px-4 py-4 text-sm font-medium text-slate-900">{{ $payment->receipt_no }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                {{ $payment->invoice?->student?->full_name }}
                                                @if ($payment->invoice?->student?->grade)
                                                    <div class="text-xs text-slate-500">{{ $payment->invoice->student->grade->name }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $payment->invoice?->invoice_no }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $payment->payment_date?->format('Y-m-d') }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ \App\Models\StudentPayment::methodOptions()[$payment->payment_method] ?? strtoupper($payment->payment_method) }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $payment->isReversed() ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                    {{ $payment->status_label }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-right text-sm font-medium text-slate-900">{{ number_format((float) $payment->amount, 2) }}</td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('student-payments.show', $payment) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">View</a>
                                                    @if (! $payment->isReversed())
                                                        <form method="POST" action="{{ route('student-payments.destroy', $payment) }}" onsubmit="return confirm('Reverse this payment? The receipt history will be kept.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">Reverse</button>
                                                        </form>
                                                    @else
                                                        <span class="text-sm font-medium text-slate-400">Reversed</span>
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
