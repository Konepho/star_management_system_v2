<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;
use App\Support\SectionScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportCardController extends Controller
{
    public function index(Request $request): View
    {
        $selectedExam = null;
        $reportCards = collect();
        $user = $request->user();

        $exams = Exam::query()
            ->with('academicYear')
            ->when($user?->requiresSectionScope(), function ($query) use ($user) {
                $academicYearIds = SectionScope::assignmentMap($user)?->keys()->map(fn ($id) => (int) $id)->all() ?? [];

                $query->whereIn('academic_year_id', $academicYearIds !== [] ? $academicYearIds : [0]);
            })
            ->orderByDesc('start_date')
            ->orderBy('name')
            ->get();

        $examId = $request->integer('exam_id');

        if ($examId) {
            $selectedExam = $exams->firstWhere('id', $examId);

            if ($selectedExam) {
                $reportCardsQuery = Student::query()
                    ->with(['enrollments' => function ($query) use ($selectedExam): void {
                        $query
                            ->where('academic_year_id', $selectedExam->academic_year_id)
                            ->with(['grade', 'section', 'academicYear'])
                            ->orderByDesc('enrollment_date')
                            ->orderByDesc('id');
                    }])
                    ->whereHas('enrollments', function (Builder $query) use ($selectedExam): void {
                        $query->where('academic_year_id', $selectedExam->academic_year_id);
                    })
                    ->whereHas('marks', function (Builder $query) use ($selectedExam): void {
                        $query
                            ->where('exam_id', $selectedExam->id)
                            ->whereNull('archived_at');
                    })
                    ->withSum([
                        'marks as total_score' => function (Builder $query) use ($selectedExam): void {
                            $query
                                ->where('exam_id', $selectedExam->id)
                                ->whereNull('archived_at');
                        },
                    ], 'score')
                    ->withSum([
                        'marks as total_max_score' => function (Builder $query) use ($selectedExam): void {
                            $query
                                ->where('exam_id', $selectedExam->id)
                                ->whereNull('archived_at');
                        },
                    ], 'max_score')
                    ->withCount([
                        'marks as subjects_recorded_count' => function (Builder $query) use ($selectedExam): void {
                            $query
                                ->where('exam_id', $selectedExam->id)
                                ->whereNull('archived_at');
                        },
                    ])
                    ->orderBy('first_name')
                    ->orderBy('last_name');

                SectionScope::restrictStudentEnrollmentScope($reportCardsQuery, $user, 'enrollments');

                $reportCards = $reportCardsQuery
                    ->get()
                    ->map(function (Student $student) {
                        $totalScore = (float) ($student->total_score ?? 0);
                        $totalMaxScore = (float) ($student->total_max_score ?? 0);
                        $percentage = $totalMaxScore > 0 ? round(($totalScore / $totalMaxScore) * 100, 2) : 0;

                        $student->report_card_percentage = $percentage;

                        return $student;
                    });
            }
        }

        return view('report-cards.index', [
            'exams' => $exams,
            'selectedExam' => $selectedExam,
            'reportCards' => $reportCards,
        ]);
    }

    public function show(Exam $exam, Student $student): View
    {
        $sectionIds = SectionScope::accessibleSectionIds(request()->user(), $exam->academic_year_id);

        if ($sectionIds !== null) {
            abort_if($sectionIds->isEmpty(), 404);

            $isInAccessibleSection = $student->enrollments()
                ->where('academic_year_id', $exam->academic_year_id)
                ->whereIn('section_id', $sectionIds->all())
                ->exists();

            abort_unless($isInAccessibleSection, 404);
        }

        $currentEnrollment = $student->enrollments()
            ->with(['academicYear', 'grade', 'section'])
            ->where('academic_year_id', $exam->academic_year_id)
            ->orderByDesc('enrollment_date')
            ->orderByDesc('id')
            ->first();

        abort_unless($currentEnrollment, 404);

        $exam->loadMissing('academicYear');

        $marks = $student->marks()
            ->with('subject')
            ->where('exam_id', $exam->id)
            ->whereNull('archived_at')
            ->orderBy(
                Subject::query()
                    ->select('name')
                    ->whereColumn('subjects.id', 'marks.subject_id')
            )
            ->get();

        abort_if($marks->isEmpty(), 404);

        $totalScore = (float) $marks->sum('score');
        $totalMaxScore = (float) $marks->sum('max_score');
        $percentage = $totalMaxScore > 0 ? round(($totalScore / $totalMaxScore) * 100, 2) : 0;

        return view('report-cards.show', [
            'exam' => $exam,
            'student' => $student,
            'currentEnrollment' => $currentEnrollment,
            'marks' => $marks,
            'totalScore' => $totalScore,
            'totalMaxScore' => $totalMaxScore,
            'percentage' => $percentage,
            'averageScore' => $marks->count() > 0 ? round($totalScore / $marks->count(), 2) : 0,
        ]);
    }
}
