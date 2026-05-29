<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Student Daily Reports</h2>
                <p class="text-sm text-slate-600">Simple daily remarks for student progress, homework, or health follow-up.</p>
            </div>
            <a href="{{ route('student-daily-reports.create') }}" class="inline-flex items-center rounded-md border border-transparent bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-600">
                Add Daily Report
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('student-daily-reports.index') }}" class="grid gap-4 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <x-searchable-student-select
                            field-id="student_filter"
                            name="student_id"
                            label="Student"
                            :selected-id="$selectedStudentId"
                            :initial-label="$selectedStudentLabel"
                            :options="$studentOptions"
                            :required="false"
                            placeholder="Type student name or admission no"
                            helper="Leave blank to show all students."
                        />
                    </div>

                    <div>
                        <x-input-label for="report_date" value="Report Date" />
                        <x-text-input
                            id="report_date"
                            name="report_date"
                            type="date"
                            class="mt-1 block w-full"
                            :value="$selectedReportDate"
                        />
                    </div>

                    <div>
                        <x-input-label for="search" value="Search Remark" />
                        <x-text-input
                            id="search"
                            name="search"
                            type="text"
                            class="mt-1 block w-full"
                            :value="$search"
                            placeholder="Search remarks or student"
                        />
                    </div>

                    <div class="lg:col-span-4 flex flex-wrap items-center justify-end gap-3">
                        @if ($selectedStudentId || $selectedReportDate || $search !== '')
                            <a href="{{ route('student-daily-reports.index') }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                                Clear
                            </a>
                        @endif

                        <x-primary-button>Filter</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Current Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Remark</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Reported By</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($reports as $report)
                                @php($currentEnrollment = $report->student?->currentEnrollment())
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ optional($report->report_date)->format('d M Y') }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        <div class="font-semibold text-slate-900">{{ $report->student?->preferred_name ?: $report->student?->name_en ?: $report->student?->full_name }}</div>
                                        <div class="text-xs text-slate-500">{{ $report->student?->admission_no }}@if($report->student?->name_mm) · {{ $report->student->name_mm }}@endif</div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        @if ($currentEnrollment)
                                            {{ $currentEnrollment->grade?->name }} / {{ $currentEnrollment->section?->name }}
                                        @else
                                            <span class="text-slate-400">No active enrollment</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        <div class="max-w-2xl whitespace-pre-line">{{ $report->remark }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $report->reportedByUser?->name ?? '-' }}</td>
                                    <td class="px-4 py-4 text-right text-sm">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('student-daily-reports.edit', $report) }}" class="inline-flex items-center rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('student-daily-reports.destroy', $report) }}" onsubmit="return confirm('Archive this daily report?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-black transition hover:bg-rose-100">
                                                    Archive
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No student daily reports found for the current filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
