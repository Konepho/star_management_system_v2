<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentDailyReportRequest;
use App\Http\Requests\UpdateStudentDailyReportRequest;
use App\Models\Student;
use App\Models\StudentDailyReport;
use App\Services\AuditLogService;
use App\Support\SectionScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDailyReportController extends Controller
{
    public function index(Request $request): View
    {
        $studentId = $request->integer('student_id') ?: null;
        $reportDate = $request->string('report_date')->toString() ?: null;
        $search = trim((string) $request->string('search')->toString());

        $reports = StudentDailyReport::query()
            ->with(['student.activeEnrollments.grade', 'student.activeEnrollments.section', 'reportedByUser']);
        $reports->whereNull('archived_at');

        SectionScope::restrictStudentEnrollmentScope($reports, $request->user(), 'student.activeEnrollments');

        if ($studentId) {
            $reports->where('student_id', $studentId);
        }

        if ($reportDate) {
            $reports->whereDate('report_date', $reportDate);
        }

        if ($search !== '') {
            $reports->where(function ($query) use ($search) {
                $query->where('remark', 'like', '%' . $search . '%')
                    ->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery
                            ->where('admission_no', 'like', '%' . $search . '%')
                            ->orWhere('name_en', 'like', '%' . $search . '%')
                            ->orWhere('name_mm', 'like', '%' . $search . '%')
                            ->orWhere('preferred_name', 'like', '%' . $search . '%');
                    });
            });
        }

        return view('student-daily-reports.index', [
            'reports' => $reports
                ->orderByDesc('report_date')
                ->orderByDesc('id')
                ->get(),
            'studentOptions' => $this->studentOptions(),
            'selectedStudentId' => $studentId ? (string) $studentId : '',
            'selectedStudentLabel' => $studentId
                ? collect($this->studentOptions())->firstWhere('id', (string) $studentId)['label'] ?? ''
                : '',
            'selectedReportDate' => $reportDate,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('student-daily-reports.create', [
            'report' => new StudentDailyReport([
                'report_date' => now()->toDateString(),
            ]),
            'studentOptions' => $this->studentOptions(),
        ]);
    }

    public function store(StoreStudentDailyReportRequest $request): RedirectResponse
    {
        $student = Student::query()->findOrFail($request->validated('student_id'));
        abort_unless(SectionScope::canAccessStudent($request->user(), $student, 'activeEnrollments'), 403);

        $report = StudentDailyReport::query()->create([
            ...$request->validated(),
            'reported_by_user_id' => $request->user()?->id,
        ]);

        app(AuditLogService::class)->log(
            'academic',
            'student_daily_reports',
            'created',
            $report,
            [],
            app(AuditLogService::class)->modelState($report),
            'Created student daily report #' . $report->id . '.',
        );

        return redirect()
            ->route('student-daily-reports.index')
            ->with('status', 'Student daily report added successfully.');
    }

    public function edit(StudentDailyReport $studentDailyReport): View
    {
        $studentDailyReport->load(['student', 'reportedByUser']);
        abort_unless(SectionScope::canAccessStudent(request()->user(), $studentDailyReport->student, 'activeEnrollments'), 403);

        return view('student-daily-reports.edit', [
            'report' => $studentDailyReport,
            'studentOptions' => $this->studentOptions(),
        ]);
    }

    public function update(UpdateStudentDailyReportRequest $request, StudentDailyReport $studentDailyReport): RedirectResponse
    {
        $student = Student::query()->findOrFail($request->validated('student_id'));
        abort_unless(SectionScope::canAccessStudent($request->user(), $student, 'activeEnrollments'), 403);
        abort_unless(SectionScope::canAccessStudent($request->user(), $studentDailyReport->student, 'activeEnrollments'), 403);
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($studentDailyReport);

        $studentDailyReport->update($request->validated());

        $auditLogService->log(
            'academic',
            'student_daily_reports',
            'updated',
            $studentDailyReport->fresh(),
            $beforeState,
            $auditLogService->modelState($studentDailyReport->fresh()),
            'Updated student daily report #' . $studentDailyReport->id . '.',
        );

        return redirect()
            ->route('student-daily-reports.index')
            ->with('status', 'Student daily report updated successfully.');
    }

    public function destroy(StudentDailyReport $studentDailyReport): RedirectResponse
    {
        $studentDailyReport->loadMissing('student');
        abort_unless(SectionScope::canAccessStudent(request()->user(), $studentDailyReport->student, 'activeEnrollments'), 403);
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($studentDailyReport);
        $wasAlreadyArchived = $studentDailyReport->isArchived();

        if (! $wasAlreadyArchived) {
            $studentDailyReport->forceFill([
                'archived_at' => now(),
            ])->save();

            $auditLogService->log(
                'academic',
                'student_daily_reports',
                'archived',
                $studentDailyReport->fresh(),
                $beforeState,
                $auditLogService->modelState($studentDailyReport->fresh()),
                'Archived student daily report #' . $studentDailyReport->id . '.',
            );
        }

        return redirect()
            ->route('student-daily-reports.index')
            ->with('status', $wasAlreadyArchived ? 'Student daily report already archived.' : 'Student daily report archived successfully.');
    }

    private function studentOptions(): array
    {
        $students = Student::query()
            ->with('activeEnrollments')
            ->orderBy('name_en')
            ->orderBy('admission_no');

        SectionScope::restrictStudentEnrollmentScope($students, request()->user(), 'activeEnrollments');

        return $students
            ->get()
            ->map(fn (Student $student) => [
                'id' => (string) $student->id,
                'label' => trim(collect([
                    $student->admission_no,
                    $student->preferred_name ?: null,
                    $student->name_en ?: $student->full_name,
                    $student->name_mm ?: null,
                ])->filter()->implode(' - ')),
            ])
            ->all();
    }
}
