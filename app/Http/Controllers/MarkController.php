<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMarkRequest;
use App\Http\Requests\UpdateMarkRequest;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\Student;
use App\Models\Subject;
use App\Services\AuditLogService;
use App\Support\SectionScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $marksQuery = Mark::query()
                ->with(['exam', 'student.activeEnrollments.grade', 'student.activeEnrollments.section', 'subject'])
                ->whereNull('archived_at')
                ->orderByDesc('created_at');

        SectionScope::restrictStudentEnrollmentScope($marksQuery, request()->user(), 'student.enrollments');

        return view('marks.index', [
            'marks' => $marksQuery->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $user = request()->user();
        $assignmentMap = SectionScope::assignmentMap($user);
        $academicYearIds = $assignmentMap?->keys()->map(fn ($id) => (int) $id)->all() ?? [];

        return view('marks.create', [
            'mark' => new Mark(),
            'exams' => Exam::query()
                ->with('academicYear')
                ->when($assignmentMap !== null, fn ($query) => $query->whereIn('academic_year_id', $academicYearIds ?: [0]))
                ->orderByDesc('start_date')
                ->get(),
            'students' => $this->accessibleStudents($user),
            'subjects' => Subject::query()->orderByDesc('is_core')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMarkRequest $request): RedirectResponse
    {
        $mark = Mark::create($request->validated());

        app(AuditLogService::class)->log(
            'academic',
            'marks',
            'created',
            $mark,
            [],
            app(AuditLogService::class)->modelState($mark),
            'Created mark record #' . $mark->id . '.',
        );

        return redirect()
            ->route('marks.index')
            ->with('status', 'Mark record created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mark $mark): View
    {
        abort_unless($this->canAccessStudentExam($mark->student, $mark->exam, request()), 403);

        $user = request()->user();
        $assignmentMap = SectionScope::assignmentMap($user);
        $academicYearIds = $assignmentMap?->keys()->map(fn ($id) => (int) $id)->all() ?? [];

        return view('marks.edit', [
            'mark' => $mark,
            'exams' => Exam::query()
                ->with('academicYear')
                ->when($assignmentMap !== null, fn ($query) => $query->whereIn('academic_year_id', $academicYearIds ?: [0]))
                ->orderByDesc('start_date')
                ->get(),
            'students' => $this->accessibleStudents($user),
            'subjects' => Subject::query()->orderByDesc('is_core')->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMarkRequest $request, Mark $mark): RedirectResponse
    {
        abort_unless($this->canAccessStudentExam($mark->student, $mark->exam, request()), 403);
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($mark);

        $mark->update($request->validated());

        $auditLogService->log(
            'academic',
            'marks',
            'updated',
            $mark->fresh(),
            $beforeState,
            $auditLogService->modelState($mark->fresh()),
            'Updated mark record #' . $mark->id . '.',
        );

        return redirect()
            ->route('marks.index')
            ->with('status', 'Mark record updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mark $mark): RedirectResponse
    {
        abort_unless($this->canAccessStudentExam($mark->student, $mark->exam, request()), 403);
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($mark);
        $wasAlreadyArchived = $mark->isArchived();

        if (! $wasAlreadyArchived) {
            $mark->forceFill([
                'archived_at' => now(),
            ])->save();

            $auditLogService->log(
                'academic',
                'marks',
                'archived',
                $mark->fresh(),
                $beforeState,
                $auditLogService->modelState($mark->fresh()),
                'Archived mark record #' . $mark->id . '.',
            );
        }

        return redirect()
            ->route('marks.index')
            ->with('status', $wasAlreadyArchived ? 'Mark record already archived.' : 'Mark record archived successfully.');
    }

    private function accessibleStudents(?\App\Models\User $user)
    {
        $students = Student::query()
            ->with(['activeEnrollments.academicYear', 'activeEnrollments.grade', 'activeEnrollments.section'])
            ->orderBy('first_name')
            ->orderBy('last_name');

        SectionScope::restrictStudentEnrollmentScope($students, $user);

        return $students->get();
    }

    private function canAccessStudentExam(Student $student, Exam $exam, Request $request): bool
    {
        $sectionIds = SectionScope::accessibleSectionIds($request->user(), $exam->academic_year_id);

        if ($sectionIds === null) {
            return true;
        }

        if ($sectionIds->isEmpty()) {
            return false;
        }

        return $student->enrollments()
            ->where('academic_year_id', $exam->academic_year_id)
            ->whereIn('section_id', $sectionIds->all())
            ->exists();
    }
}
