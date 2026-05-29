<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ $registration->student?->full_name }}</h2>
                <p class="mt-1 text-sm text-slate-700">{{ $registration->session?->name }} • {{ $registration->session?->exam_body }}</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <a href="{{ route('external-exam-registrations.edit', $registration) }}" class="inline-flex items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600">Edit Registration / Result</a>
                <a href="{{ route('external-exam-registrations.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-1">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-slate-900">Registration Details</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="font-medium text-slate-500">Student</dt>
                                <dd class="text-slate-900">{{ $registration->student?->full_name }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Admission No</dt>
                                <dd class="text-slate-900">{{ $registration->student?->admission_no }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Current Class</dt>
                                <dd class="text-slate-900">{{ $registration->student?->currentEnrollment()?->grade?->name ?? '—' }} @if($registration->student?->currentEnrollment()?->section) - {{ $registration->student->currentEnrollment()->section->name }} @endif</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">External Exam Session</dt>
                                <dd class="text-slate-900">{{ $registration->session?->name }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Exam Body</dt>
                                <dd class="text-slate-900">{{ $registration->session?->exam_body }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Level</dt>
                                <dd class="text-slate-900">{{ $registration->session?->level ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Exam Date</dt>
                                <dd class="text-slate-900">{{ $registration->session?->exam_date?->format('Y-m-d') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Registration Status</dt>
                                <dd class="text-slate-900">{{ \App\Models\ExternalExamRegistration::statusOptions()[$registration->status] ?? ucfirst($registration->status) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Candidate No</dt>
                                <dd class="text-slate-900">{{ $registration->candidate_no ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Result Status</dt>
                                <dd class="text-slate-900">{{ \App\Models\ExternalExamRegistration::resultStatusOptions()[$registration->result_status] ?? ucfirst($registration->result_status) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Score</dt>
                                <dd class="text-slate-900">{{ $registration->score ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Grade</dt>
                                <dd class="text-slate-900">{{ $registration->grade ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Result Remarks</dt>
                                <dd class="text-slate-900">{{ $registration->result_remarks ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Notes</dt>
                                <dd class="text-slate-900">{{ $registration->notes ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-1">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-slate-900">Fee Summary</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="font-medium text-slate-500">Exam Fee</dt>
                                <dd class="text-slate-900">{{ number_format((float) $registration->fee_amount, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Discount</dt>
                                <dd class="text-slate-900">{{ number_format((float) $registration->discount_amount, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Total Due</dt>
                                <dd class="text-lg font-semibold text-slate-900">{{ number_format((float) $registration->total_amount, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Paid</dt>
                                <dd class="text-slate-900">{{ number_format((float) $registration->paid_amount, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Balance Due</dt>
                                <dd class="text-lg font-semibold text-rose-700">{{ number_format((float) $registration->balance_due, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Payment Status</dt>
                                <dd class="text-slate-900">{{ $registration->payment_status_label }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-1">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-slate-900">Collect Payment</h3>
                        @if ($registration->balance_due <= 0)
                            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">This external exam registration is fully paid.</div>
                        @else
                            <form method="POST" action="{{ route('external-exam-payments.store') }}" class="mt-4 space-y-4">
                                @csrf
                                <input type="hidden" name="external_exam_registration_id" value="{{ $registration->id }}">

                                <div>
                                    <x-input-label for="payment_date" :value="__('Payment Date')" />
                                    <x-text-input id="payment_date" name="payment_date" type="date" class="mt-1 block w-full" :value="old('payment_date', now()->format('Y-m-d'))" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('payment_date')" />
                                </div>

                                <div>
                                    <x-input-label for="amount" :value="__('Amount')" />
                                    <x-text-input id="amount" name="amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" :value="old('amount', number_format((float) $registration->balance_due, 2, '.', ''))" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                                </div>

                                <div>
                                    <x-input-label for="payment_method" :value="__('Payment Method')" />
                                    <select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        @foreach (\App\Models\ExternalExamPayment::methodOptions() as $value => $label)
                                            <option value="{{ $value }}" @selected(old('payment_method', \App\Models\ExternalExamPayment::METHOD_CASH) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('payment_method')" />
                                </div>

                                <div>
                                    <x-input-label for="reference_no" :value="__('Reference No')" />
                                    <x-text-input id="reference_no" name="reference_no" type="text" class="mt-1 block w-full" :value="old('reference_no')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('reference_no')" />
                                </div>

                                <div>
                                    <x-input-label for="notes" :value="__('Notes')" />
                                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('external_exam_registration_id')" />
                                </div>

                                <x-primary-button>Collect External Exam Fee</x-primary-button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-slate-900">Payment History</h3>
                        <a href="{{ route('external-exam-payments.index') }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">View all external exam payments</a>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Receipt No</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Method</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Reference</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($registration->payments as $payment)
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-medium text-slate-900">
                                            <a href="{{ route('external-exam-payments.show', $payment) }}" class="text-sky-700 hover:text-sky-600">{{ $payment->receipt_no }}</a>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $payment->payment_date?->format('Y-m-d') }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ \App\Models\ExternalExamPayment::methodOptions()[$payment->payment_method] ?? strtoupper($payment->payment_method) }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $payment->reference_no ?: '—' }}</td>
                                        <td class="px-4 py-4 text-right text-sm font-medium text-slate-900">{{ number_format((float) $payment->amount, 2) }}</td>
                                        <td class="px-4 py-4 text-right">
                                            @if (! $payment->isReversed())
                                                <form method="POST" action="{{ route('external-exam-payments.destroy', $payment) }}" onsubmit="return confirm('Reverse this external exam payment? The receipt history will be kept.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">Reverse</button>
                                                </form>
                                            @else
                                                <span class="text-sm font-medium text-slate-400">Reversed</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">No external exam payments collected yet.</td>
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
