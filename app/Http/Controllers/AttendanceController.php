<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use App\Services\AuditLogService;
use App\Support\SectionScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $selectedDate = $request->string('attendance_date')->toString() ?: now()->toDateString();
        $academicYears = AcademicYear::query()->orderByDesc('start_date')->orderByDesc('id')->get();
        $selectedAcademicYearId = $request->integer('academic_year_id') ?: $academicYears->firstWhere('is_current', true)?->id;
        $sections = $this->availableSections($request, $selectedAcademicYearId);
        $selectedSectionId = $request->integer('section_id');
        $selectedSection = $selectedSectionId ? $sections->firstWhere('id', $selectedSectionId) : null;
        $selectedSectionId = $selectedSection?->id;
        $registerEnrollments = $this->registerEnrollments($selectedAcademicYearId, $selectedSectionId);
        $attendanceRecords = $this->attendanceRecords($selectedDate, $selectedAcademicYearId, $selectedSectionId);

        return view('attendances.index', [
            'selectedDate' => $selectedDate,
            'academicYears' => $academicYears,
            'selectedAcademicYearId' => $selectedAcademicYearId,
            'sections' => $sections,
            'selectedSectionId' => $selectedSectionId,
            'selectedSection' => $selectedSection,
            'registerEnrollments' => $registerEnrollments,
            'attendanceRecords' => $attendanceRecords,
        ]);
    }

    public function create(Request $request): View
    {
        $academicYears = AcademicYear::query()->orderByDesc('start_date')->orderByDesc('id')->get();
        $selectedAcademicYearId = $request->integer('academic_year_id') ?: $academicYears->firstWhere('is_current', true)?->id;
        $sections = $this->availableSections($request, $selectedAcademicYearId);
        $selectedSectionId = $request->integer('section_id');
        $selectedSection = $selectedSectionId ? $sections->firstWhere('id', $selectedSectionId) : null;
        $selectedSectionId = $selectedSection?->id;
        $selectedDate = $request->string('attendance_date')->toString() ?: now()->toDateString();
        $registerEnrollments = $this->registerEnrollments($selectedAcademicYearId, $selectedSectionId);
        $existingAttendances = $this->existingAttendances($registerEnrollments, $selectedDate);

        return view('attendances.create', [
            'academicYears' => $academicYears,
            'selectedAcademicYearId' => $selectedAcademicYearId,
            'sections' => $sections,
            'selectedSectionId' => $selectedSectionId,
            'selectedSection' => $selectedSection,
            'selectedDate' => $selectedDate,
            'registerEnrollments' => $registerEnrollments,
            'existingAttendances' => $existingAttendances,
        ]);
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $selectedDate = $validated['attendance_date'];
        $selectedAcademicYearId = (int) $validated['academic_year_id'];
        $selectedSectionId = (int) $validated['section_id'];
        $statusCounts = collect($validated['attendances'])
            ->countBy('status')
            ->sortKeys()
            ->all();

        DB::transaction(function () use ($validated, $selectedDate) {
            foreach ($validated['attendances'] as $attendanceInput) {
                $attendance = Attendance::query()
                    ->where('student_id', $attendanceInput['student_id'])
                    ->whereDate('attendance_date', $selectedDate)
                    ->first();

                if (! $attendance) {
                    $attendance = new Attendance([
                        'student_id' => $attendanceInput['student_id'],
                        'attendance_date' => $selectedDate,
                    ]);
                }

                $attendance->status = $attendanceInput['status'];
                $attendance->remarks = $attendanceInput['remarks'] ?? null;
                $attendance->save();
            }
        });

        app(AuditLogService::class)->log(
            'academic',
            'attendance',
            'saved_register',
            null,
            [],
            [
                'attendance_date' => $selectedDate,
                'academic_year_id' => $selectedAcademicYearId,
                'section_id' => $selectedSectionId,
                'student_count' => count($validated['attendances']),
                'status_counts' => $statusCounts,
            ],
            'Saved class attendance register.',
        );

        return redirect()
            ->route('attendances.create', [
                'academic_year_id' => $selectedAcademicYearId,
                'section_id' => $selectedSectionId,
                'attendance_date' => $selectedDate,
            ])
            ->with('status', 'Class attendance saved successfully.');
    }

    private function registerEnrollments(?int $academicYearId, ?int $sectionId): Collection
    {
        if (! $academicYearId || ! $sectionId) {
            return collect();
        }

        return Enrollment::query()
            ->with(['student', 'academicYear', 'grade', 'section'])
            ->where('academic_year_id', $academicYearId)
            ->where('section_id', $sectionId)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->orderBy(
                Student::query()
                    ->selectRaw("COALESCE(NULLIF(name_en, ''), NULLIF(preferred_name, ''), NULLIF(first_name, ''), admission_no)")
                    ->whereColumn('students.id', 'enrollments.student_id')
            )
            ->orderBy(
                Student::query()
                    ->select('admission_no')
                    ->whereColumn('students.id', 'enrollments.student_id')
            )
            ->get();
    }

    private function existingAttendances(Collection $registerEnrollments, string $selectedDate): Collection
    {
        if ($registerEnrollments->isEmpty()) {
            return collect();
        }

        return Attendance::query()
            ->whereDate('attendance_date', $selectedDate)
            ->whereIn('student_id', $registerEnrollments->pluck('student_id')->all())
            ->get()
            ->keyBy('student_id');
    }

    private function attendanceRecords(string $selectedDate, ?int $academicYearId, ?int $sectionId): Collection
    {
        if (! $academicYearId || ! $sectionId) {
            return collect();
        }

        return Attendance::query()
            ->with('student')
            ->whereDate('attendance_date', $selectedDate)
            ->whereIn('student_id', Enrollment::query()
                ->where('academic_year_id', $academicYearId)
                ->where('section_id', $sectionId)
                ->select('student_id'))
            ->orderBy(
                Student::query()
                    ->selectRaw("COALESCE(NULLIF(name_en, ''), NULLIF(preferred_name, ''), NULLIF(first_name, ''), admission_no)")
                    ->whereColumn('students.id', 'attendances.student_id')
            )
            ->get();
    }

    private function availableSections(Request $request, ?int $academicYearId): Collection
    {
        $sections = Section::query()->with('grade')->orderBy('grade_id')->orderBy('name');
        $sectionIds = SectionScope::accessibleSectionIds($request->user(), $academicYearId);

        if ($sectionIds !== null) {
            if ($sectionIds->isEmpty()) {
                return collect();
            }

            $sections->whereIn('id', $sectionIds->all());
        }

        return $sections->get();
    }
}
