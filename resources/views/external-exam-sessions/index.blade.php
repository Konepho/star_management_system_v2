<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">External Exam Sessions</h2>
                <p class="mt-1 text-sm text-slate-700">Create outside exam windows like YLE or mathematics competitions, including the default fee to collect.</p>
            </div>
            <a href="{{ route('external-exam-sessions.create') }}" class="inline-flex items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600">Add External Exam Session</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($sessions->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No external exam sessions yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Create sessions for YLE, Olympiads, or any outside exam your students need to register for.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Session</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Academic Year</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Dates</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Fee</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($sessions as $session)
                                        <tr>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $session->name }}</div>
                                                <div class="text-sm text-slate-500">{{ $session->exam_body }}{{ $session->level ? ' • ' . $session->level : '' }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $session->academicYear?->name }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <div>Deadline: {{ $session->registration_deadline?->format('Y-m-d') ?? '—' }}</div>
                                                <div>Exam: {{ $session->exam_date?->format('Y-m-d') ?? '—' }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm font-medium text-slate-900">{{ number_format((float) $session->fee_amount, 2) }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ \App\Models\ExternalExamSession::statusOptions()[$session->status] ?? ucfirst($session->status) }}</td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('external-exam-registrations.create', ['external_exam_session_id' => $session->id]) }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-600">Register Students</a>
                                                    <a href="{{ route('external-exam-sessions.edit', $session) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Edit</a>
                                                    <form method="POST" action="{{ route('external-exam-sessions.destroy', $session) }}" onsubmit="return confirm('Cancel this external exam session?');">
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
