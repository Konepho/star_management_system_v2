@props([
    'report',
    'studentOptions',
])

<div class="space-y-6">
    <div class="grid gap-6 md:grid-cols-2">
        <div class="md:col-span-2">
            <x-searchable-student-select
                field-id="student_id"
                name="student_id"
                label="Student"
                :selected-id="old('student_id', $report->student_id)"
                :initial-label="old('student_id') ? collect($studentOptions)->firstWhere('id', (string) old('student_id'))['label'] ?? '' : ($report->student ? trim(collect([$report->student->admission_no, $report->student->preferred_name ?: null, $report->student->name_en ?: $report->student->full_name, $report->student->name_mm ?: null])->filter()->implode(' - ')) : '')"
                :options="$studentOptions"
            />
        </div>

        <div>
            <x-input-label for="report_date" value="Report Date" />
            <x-text-input
                id="report_date"
                name="report_date"
                type="date"
                class="mt-1 block w-full"
                :value="old('report_date', optional($report->report_date)->format('Y-m-d') ?? $report->report_date)"
                required
            />
            <x-input-error class="mt-2" :messages="$errors->get('report_date')" />
        </div>

        <div>
            <x-input-label for="reported_by" value="Reported By" />
            <x-text-input
                id="reported_by"
                type="text"
                class="mt-1 block w-full bg-slate-50"
                :value="$report->reportedByUser?->name ?? auth()->user()?->name"
                readonly
            />
        </div>
    </div>

    <div>
        <x-input-label for="remark" value="Remark" />
        <textarea
            id="remark"
            name="remark"
            rows="6"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            required
        >{{ old('remark', $report->remark) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('remark')" />
    </div>
</div>
