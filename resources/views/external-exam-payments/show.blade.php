<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ $payment->receipt_no }}</h2>
                <p class="mt-1 text-sm text-slate-700">External exam payment receipt details.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <a href="{{ route('external-exam-registrations.show', $payment->registration) }}" class="inline-flex items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600">View Registration</a>
                <a href="{{ route('external-exam-payments.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Payments</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-slate-900">Payment Details</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="font-medium text-slate-500">Receipt No</dt>
                                <dd class="text-slate-900">{{ $payment->receipt_no }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Payment Date</dt>
                                <dd class="text-slate-900">{{ $payment->payment_date?->format('Y-m-d') }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Payment Method</dt>
                                <dd class="text-slate-900">{{ \App\Models\ExternalExamPayment::methodOptions()[$payment->payment_method] ?? strtoupper($payment->payment_method) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Status</dt>
                                <dd class="text-slate-900">{{ $payment->status_label }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Reference No</dt>
                                <dd class="text-slate-900">{{ $payment->reference_no ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Amount</dt>
                                <dd class="text-lg font-semibold text-slate-900">{{ number_format((float) $payment->amount, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Reversed At</dt>
                                <dd class="text-slate-900">{{ $payment->reversed_at?->format('Y-m-d H:i') ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Reversal Reason</dt>
                                <dd class="text-slate-900">{{ $payment->reversal_reason ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Notes</dt>
                                <dd class="text-slate-900">{{ $payment->notes ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-slate-900">Registration Summary</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="font-medium text-slate-500">Student</dt>
                                <dd class="text-slate-900">{{ $payment->registration?->student?->full_name }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Admission No</dt>
                                <dd class="text-slate-900">{{ $payment->registration?->student?->admission_no ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">External Exam Session</dt>
                                <dd class="text-slate-900">{{ $payment->registration?->session?->name }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Exam Body</dt>
                                <dd class="text-slate-900">{{ $payment->registration?->session?->exam_body }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Academic Year</dt>
                                <dd class="text-slate-900">{{ $payment->registration?->session?->academicYear?->name }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Total Due</dt>
                                <dd class="text-slate-900">{{ number_format((float) $payment->registration?->total_amount, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Paid So Far</dt>
                                <dd class="text-slate-900">{{ number_format((float) $payment->registration?->paid_amount, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Balance Due</dt>
                                <dd class="text-lg font-semibold text-rose-700">{{ number_format((float) $payment->registration?->balance_due, 2) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
