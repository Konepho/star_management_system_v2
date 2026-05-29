<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Report Card') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $student->full_name }} • {{ $exam->name }}</p>
            </div>
            <a href="{{ route('report-cards.index', ['exam_id' => $exam->id]) }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to Report Cards</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg lg:col-span-2">
                    <div class="p-6 text-gray-900">
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Student</div>
                                <div class="mt-1 text-sm font-medium text-slate-900">{{ $student->full_name }}</div>
                                <div class="text-sm text-slate-500">{{ $student->admission_no }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Class</div>
                                <div class="mt-1 text-sm font-medium text-slate-900">{{ $currentEnrollment?->grade?->name }}</div>
                                <div class="text-sm text-slate-500">{{ $currentEnrollment?->section?->name }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Academic Year</div>
                                <div class="mt-1 text-sm font-medium text-slate-900">{{ $exam->academicYear?->name }}</div>
                                <div class="text-sm text-slate-500">{{ $exam->term ?: 'General Exam' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold text-slate-900">Summary</h3>
                        <dl class="mt-4 space-y-4">
                            <div>
                                <dt class="text-sm text-slate-500">Total Score</dt>
                                <dd class="text-lg font-semibold text-slate-900">{{ number_format($totalScore, 2) }} / {{ number_format($totalMaxScore, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-slate-500">Average Score</dt>
                                <dd class="text-lg font-semibold text-slate-900">{{ number_format($averageScore, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-slate-500">Percentage</dt>
                                <dd class="text-lg font-semibold text-emerald-700">{{ number_format($percentage, 2) }}%</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-slate-500">Subjects Recorded</dt>
                                <dd class="text-lg font-semibold text-slate-900">{{ $marks->count() }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Subject</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Score</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Percentage</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Grade</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($marks as $mark)
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-medium text-slate-900">{{ $mark->subject?->name }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ number_format((float) $mark->score, 2) }} / {{ number_format((float) $mark->max_score, 2) }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $mark->max_score > 0 ? number_format(((float) $mark->score / (float) $mark->max_score) * 100, 2) : number_format(0, 2) }}%</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $mark->grade_letter ?: '—' }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ ucfirst($mark->status) }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $mark->remarks ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-900">Overall</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-900">{{ number_format($totalScore, 2) }} / {{ number_format($totalMaxScore, 2) }}</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-emerald-700">{{ number_format($percentage, 2) }}%</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-900" colspan="3">Average: {{ number_format($averageScore, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
