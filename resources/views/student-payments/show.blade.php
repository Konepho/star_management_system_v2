<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ $payment->receipt_no }}</h2>
                <p class="mt-1 text-sm text-slate-700">Payment receipt and invoice reference.</p>
            </div>
            <a href="{{ route('student-payments.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Back to Payments
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-slate-900">Payment Details</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div><dt class="font-medium text-slate-500">Receipt No</dt><dd class="text-slate-900">{{ $payment->receipt_no }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Student</dt><dd class="text-slate-900">{{ $payment->invoice?->student?->full_name }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Invoice</dt><dd class="text-slate-900">{{ $payment->invoice?->invoice_no }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Academic Year</dt><dd class="text-slate-900">{{ $payment->invoice?->academicYear?->name }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Payment Date</dt><dd class="text-slate-900">{{ $payment->payment_date?->format('Y-m-d') }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Method</dt><dd class="text-slate-900">{{ \App\Models\StudentPayment::methodOptions()[$payment->payment_method] ?? strtoupper($payment->payment_method) }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Status</dt><dd class="text-slate-900">{{ $payment->status_label }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Reference No</dt><dd class="text-slate-900">{{ $payment->reference_no ?: '—' }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Amount</dt><dd class="text-lg font-semibold text-slate-900">{{ number_format((float) $payment->amount, 2) }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Reversed At</dt><dd class="text-slate-900">{{ $payment->reversed_at?->format('Y-m-d H:i') ?: '—' }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Reversal Reason</dt><dd class="text-slate-900">{{ $payment->reversal_reason ?: '—' }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Notes</dt><dd class="text-slate-900">{{ $payment->notes ?: '—' }}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-slate-900">Invoice Summary</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div><dt class="font-medium text-slate-500">Invoice Subtotal</dt><dd class="text-slate-900">{{ number_format((float) $payment->invoice?->subtotal_amount, 2) }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Discount Amount</dt><dd class="font-semibold text-emerald-700">{{ number_format((float) $payment->invoice?->discount_amount, 2) }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Invoice Total</dt><dd class="text-slate-900">{{ number_format((float) $payment->invoice?->total_amount, 2) }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Paid So Far</dt><dd class="text-slate-900">{{ number_format((float) $payment->invoice?->paid_amount, 2) }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Balance Due</dt><dd class="text-lg font-semibold text-rose-700">{{ number_format((float) $payment->invoice?->balance_due, 2) }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Invoice Status</dt><dd class="text-slate-900">{{ ucfirst((string) $payment->invoice?->status) }}</dd></div>
                            <div><dt class="font-medium text-slate-500">Payment Timing Rule</dt><dd class="text-slate-900">{{ $payment->invoice?->payment_timing_status_label ?? '—' }}</dd></div>
                        </dl>

                        @if ($payment->invoice && $payment->invoice->discounts->isNotEmpty())
                            <div class="mt-6 rounded-lg border border-slate-200 p-4">
                                <h4 class="text-sm font-semibold text-slate-900">Applied Discounts</h4>
                                <div class="mt-3 space-y-2">
                                    @foreach ($payment->invoice->discounts as $discount)
                                        <div class="flex items-start justify-between gap-3 text-sm">
                                            <div>
                                                <div class="font-medium text-slate-900">{{ $discount->discountDefinition?->name ?? $discount->reason }}</div>
                                                <div class="text-xs text-slate-500">{{ $discount->item?->description }}</div>
                                            </div>
                                            <div class="text-right font-semibold text-emerald-700">{{ number_format((float) $discount->amount, 2) }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mt-6">
                            <a href="{{ route('student-invoices.show', $payment->invoice) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">
                                Open related invoice
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
