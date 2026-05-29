<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FeePlan;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use App\Services\AuditLogService;
use App\Support\SectionScope;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function index(): View
    {
        $enrollments = Enrollment::query()
            ->with(['student', 'academicYear', 'grade', 'section', 'feePlan'])
            ->orderByDesc('enrollment_date')
            ->orderByDesc('id');

        SectionScope::restrictEnrollmentQuery($enrollments, request()->user());

        return view('enrollments.index', [
            'enrollments' => $enrollments->get(),
        ]);
    }

    public function create(): View
    {
        $user = request()->user();

        return view('enrollments.create', [
            'enrollment' => new Enrollment([
                'enrollment_date' => now()->toDateString(),
                'status' => Enrollment::STATUS_ACTIVE,
            ]),
            'students' => $this->accessibleStudents($user),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'feePlans' => FeePlan::query()->with('academicYear')->orderBy('name')->get(),
            'grades' => Grade::query()->orderBy('sort_order')->orderBy('name')->get(),
            'sections' => $this->accessibleSections($user),
        ]);
    }

    public function store(StoreEnrollmentRequest $request): RedirectResponse
    {
        $enrollment = DB::transaction(function () use ($request) {
            $enrollment = Enrollment::query()->create($request->validated());
            $this->normalizeActiveEnrollments($enrollment);
            return $enrollment;
        });

        app(AuditLogService::class)->log(
            'academic',
            'enrollments',
            'created',
            $enrollment,
            [],
            app(AuditLogService::class)->modelState($enrollment),
            'Created enrollment for student #' . $enrollment->student_id . '.',
        );

        return redirect()
            ->route('enrollments.index')
            ->with('status', 'Student enrolled successfully.');
    }

    public function edit(Enrollment $enrollment): View
    {
        abort_unless(SectionScope::canAccessAcademicYearSection(request()->user(), $enrollment->academic_year_id, $enrollment->section_id), 403);

        $user = request()->user();

        return view('enrollments.edit', [
            'enrollment' => $enrollment->load(['student', 'academicYear', 'grade', 'section', 'feePlan']),
            'students' => $this->accessibleStudents($user),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'feePlans' => FeePlan::query()->with('academicYear')->orderBy('name')->get(),
            'grades' => Grade::query()->orderBy('sort_order')->orderBy('name')->get(),
            'sections' => $this->accessibleSections($user),
        ]);
    }

    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment): RedirectResponse
    {
        abort_unless(SectionScope::canAccessAcademicYearSection($request->user(), $enrollment->academic_year_id, $enrollment->section_id), 403);
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($enrollment);

        DB::transaction(function () use ($request, $enrollment) {
            $enrollment->update($request->validated());
            $this->normalizeActiveEnrollments($enrollment->fresh());
        });

        $auditLogService->log(
            'academic',
            'enrollments',
            'updated',
            $enrollment->fresh(),
            $beforeState,
            $auditLogService->modelState($enrollment->fresh()),
            'Updated enrollment #' . $enrollment->id . '.',
        );

        return redirect()
            ->route('enrollments.index')
            ->with('status', 'Enrollment updated successfully.');
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        abort_unless(SectionScope::canAccessAcademicYearSection(request()->user(), $enrollment->academic_year_id, $enrollment->section_id), 403);
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($enrollment);
        $wasAlreadyInactive = $enrollment->status === Enrollment::STATUS_INACTIVE;

        if (! $wasAlreadyInactive) {
            DB::transaction(fn () => $enrollment->update([
                'status' => Enrollment::STATUS_INACTIVE,
            ]));

            $auditLogService->log(
                'academic',
                'enrollments',
                'deactivated',
                $enrollment->fresh(),
                $beforeState,
                $auditLogService->modelState($enrollment->fresh()),
                'Deactivated enrollment #' . $enrollment->id . '.',
            );
        }

        return redirect()
            ->route('enrollments.index')
            ->with('status', $wasAlreadyInactive ? 'Enrollment already inactive.' : 'Enrollment deactivated successfully.');
    }

    protected function normalizeActiveEnrollments(Enrollment $enrollment): void
    {
        if ($enrollment->status !== Enrollment::STATUS_ACTIVE) {
            return;
        }

        $activeSiblings = Enrollment::query()
            ->with('academicYear')
            ->where('student_id', $enrollment->student_id)
            ->whereKeyNot($enrollment->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->get();

        if ($activeSiblings->isEmpty()) {
            return;
        }

        if ($this->hasNewerActiveEnrollment($enrollment, $activeSiblings)) {
            throw ValidationException::withMessages([
                'status' => 'A newer active enrollment already exists for this student. Use completed, transferred, or inactive for historical corrections.',
            ]);
        }

        $olderActiveEnrollmentIds = $activeSiblings
            ->filter(fn (Enrollment $activeEnrollment) => $this->compareEnrollmentOrder($enrollment, $activeEnrollment) > 0)
            ->pluck('id')
            ->all();

        if ($olderActiveEnrollmentIds !== []) {
            Enrollment::query()
                ->whereIn('id', $olderActiveEnrollmentIds)
                ->update(['status' => Enrollment::STATUS_COMPLETED]);
        }
    }

    protected function hasNewerActiveEnrollment(Enrollment $enrollment, EloquentCollection $activeSiblings): bool
    {
        return $activeSiblings
            ->contains(fn (Enrollment $activeEnrollment) => $this->compareEnrollmentOrder($activeEnrollment, $enrollment) > 0);
    }

    protected function compareEnrollmentOrder(Enrollment $left, Enrollment $right): int
    {
        $leftAcademicYear = $left->academicYear ?? $left->academicYear()->first();
        $rightAcademicYear = $right->academicYear ?? $right->academicYear()->first();

        $leftStartDate = $leftAcademicYear?->start_date?->format('Y-m-d');
        $rightStartDate = $rightAcademicYear?->start_date?->format('Y-m-d');

        if ($leftStartDate !== $rightStartDate) {
            return $leftStartDate <=> $rightStartDate;
        }

        $leftEnrollmentDate = $left->enrollment_date?->format('Y-m-d');
        $rightEnrollmentDate = $right->enrollment_date?->format('Y-m-d');

        if ($leftEnrollmentDate !== $rightEnrollmentDate) {
            return $leftEnrollmentDate <=> $rightEnrollmentDate;
        }

        return ($left->id ?? 0) <=> ($right->id ?? 0);
    }

    private function accessibleStudents($user)
    {
        $students = Student::query()
            ->with('activeEnrollments')
            ->orderBy('first_name')
            ->orderBy('last_name');

        SectionScope::restrictStudentEnrollmentScope($students, $user, 'activeEnrollments');

        return $students->get();
    }

    private function accessibleSections($user)
    {
        $sections = Section::query()->with('grade')->orderBy('grade_id')->orderBy('name');
        $assignmentMap = SectionScope::assignmentMap($user);

        if ($assignmentMap !== null) {
            $sectionIds = $assignmentMap->flatten()->map(fn ($id) => (int) $id)->unique()->values();

            if ($sectionIds->isEmpty()) {
                return collect();
            }

            $sections->whereIn('id', $sectionIds->all());
        }

        return $sections->get();
    }
}
