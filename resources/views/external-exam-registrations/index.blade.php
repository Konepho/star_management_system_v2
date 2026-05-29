<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">External Exam Registrations</h2>
                <p class="mt-1 text-sm text-slate-700">Register students for outside exams, collect their exam fees, and record final results.</p>
            </div>
            <a href="{{ route('external-exam-registrations.create') }}" class="inline-flex items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600">Register Student</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($registrations->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No external exam registrations yet</h3>
                            <p class="mt-2 text-sm text-slate-500">After creating an external exam session, register each student who will sit for that exam.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Student</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Session</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Fee</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Payments</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Result</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($registrations as $registration)
                                        <tr>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $registration->student?->full_name }}</div>
                                                <div class="text-sm text-slate-500">{{ $registration->student?->admission_no }}</div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $registration->session?->name }}</div>
                                                <div class="text-sm text-slate-500">{{ $registration->session?->exam_body }}{{ $registration->session?->level ? ' • ' . $registration->session->level : '' }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <div>Total: <span class="font-medium text-slate-900">{{ number_format((float) $registration->total_amount, 2) }}</span></div>
                                                <div>Discount: {{ number_format((float) $registration->discount_amount, 2) }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <div>Paid: <span class="font-medium text-slate-900">{{ number_format((float) $registration->paid_amount, 2) }}</span></div>
                                                <div>Balance: <span class="font-medium text-slate-900">{{ number_format((float) $registration->balance_due, 2) }}</span></div>
                                                <div class="mt-1 text-xs text-sky-700">{{ $registration->payment_status_label }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <div>Status: <span class="font-medium text-slate-900">{{ \App\Models\ExternalExamRegistration::resultStatusOptions()[$registration->result_status] ?? ucfirst($registration->result_status) }}</span></div>
                                                <div>Grade: <span class="font-medium text-slate-900">{{ $registration->grade ?: '—' }}</span></div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('external-exam-registrations.show', $registration) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">View</a>
                                                    <a href="{{ route('external-exam-registrations.edit', $registration) }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-600">Edit</a>
                                                    <form method="POST" action="{{ route('external-exam-registrations.destroy', $registration) }}" onsubmit="return confirm('Cancel this external exam registration?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">Cancel</button>
                                                    </form>
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
