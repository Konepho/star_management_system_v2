<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">External Exam Payments</h2>
                <p class="mt-1 text-sm text-slate-700">Review collected fees for outside exams like YLE or mathematics competitions.</p>
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
                    <form method="GET" action="{{ route('external-exam-payments.index') }}" class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                            <div class="flex-1">
                                <label for="search" class="text-sm font-semibold text-slate-800">Search Payments</label>
                                <input
                                    id="search"
                                    name="search"
                                    type="text"
                                    value="{{ $search }}"
                                    placeholder="Search by receipt no, student, admission no, exam session, or reference"
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                >
                            </div>
                            <div class="flex gap-3">
                                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600">
                                    Search
                                </button>
                                @if ($search !== '')
                                    <a href="{{ route('external-exam-payments.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-white">
                                        Clear
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    @if ($payments->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $search !== '' ? 'No matching external exam payments found' : 'No external exam payments yet' }}</h3>
                            <p class="mt-2 text-sm text-slate-500">
                                {{ $search !== '' ? 'Try another receipt number, student, or exam session.' : 'Payments will appear here after you collect fees from external exam registrations.' }}
                            </p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Receipt</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Student</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Session</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Payment</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($payments as $payment)
                                        <tr>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $payment->receipt_no }}</div>
                                                <div class="text-sm text-slate-500">{{ $payment->payment_date?->format('Y-m-d') }}</div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $payment->registration?->student?->full_name }}</div>
                                                <div class="text-sm text-slate-500">{{ $payment->registration?->student?->admission_no ?: '—' }}</div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $payment->registration?->session?->name }}</div>
                                                <div class="text-sm text-slate-500">
                                                    {{ $payment->registration?->session?->exam_body }}
                                                    @if ($payment->registration?->session?->level)
                                                        • {{ $payment->registration->session->level }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <div>{{ \App\Models\ExternalExamPayment::methodOptions()[$payment->payment_method] ?? strtoupper($payment->payment_method) }}</div>
                                                <div class="text-xs text-slate-500">{{ $payment->reference_no ?: '—' }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $payment->isReversed() ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                    {{ $payment->status_label }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-right text-sm font-medium text-slate-900">{{ number_format((float) $payment->amount, 2) }}</td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('external-exam-payments.show', $payment) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">View</a>
                                                    <a href="{{ route('external-exam-registrations.show', $payment->registration) }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-600">Registration</a>
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
