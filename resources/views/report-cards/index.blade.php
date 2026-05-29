<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Report Cards') }}</h2>
                <p class="mt-1 text-sm text-slate-500">Choose an exam to see student result summaries and open full report cards.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('report-cards.index') }}" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                        <div>
                            <label for="exam_id" class="block text-sm font-medium text-slate-700">Exam</label>
                            <select id="exam_id" name="exam_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                <option value="">Select an exam</option>
                                @foreach ($exams as $exam)
                                    <option value="{{ $exam->id }}" @selected($selectedExam?->id === $exam->id)>
                                        {{ $exam->name }} ({{ $exam->academicYear?->name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">View Report Cards</button>
                            <a href="{{ route('report-cards.index') }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (! $selectedExam)
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">Select an exam to begin</h3>
                            <p class="mt-2 text-sm text-slate-500">Once marks are recorded for an exam, each student report card will appear here.</p>
                        </div>
                    @elseif ($reportCards->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No report cards available yet</h3>
                            <p class="mt-2 text-sm text-slate-500">This exam does not have any recorded marks for students in {{ $selectedExam->academicYear?->name }}.</p>
                            <div class="mt-4">
                                <a href="{{ route('marks.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Add Marks</a>
                            </div>
                        </div>
                    @else
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $selectedExam->name }}</h3>
                            <p class="text-sm text-slate-500">{{ $selectedExam->academicYear?->name }} {{ $selectedExam->term ? '• '.$selectedExam->term : '' }}</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Student</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Class</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Subjects</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Total</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Percentage</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($reportCards as $reportCard)
                                        @php($currentEnrollment = $reportCard->enrollments->first())
                                        <tr>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $reportCard->full_name }}</div>
                                                <div class="text-sm text-slate-500">{{ $reportCard->admission_no }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $currentEnrollment?->grade?->name ?? '—' }} / {{ $currentEnrollment?->section?->name ?? '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $reportCard->subjects_recorded_count }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                {{ number_format((float) ($reportCard->total_score ?? 0), 2) }} /
                                                {{ number_format((float) ($reportCard->total_max_score ?? 0), 2) }}
                                            </td>
                                            <td class="px-4 py-4 text-sm font-medium text-slate-700">{{ number_format((float) $reportCard->report_card_percentage, 2) }}%</td>
                                            <td class="px-4 py-4 text-right">
                                                <a href="{{ route('report-cards.show', [$selectedExam, $reportCard]) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Open Report Card</a>
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
