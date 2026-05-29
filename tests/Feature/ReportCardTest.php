<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Mark;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_report_card_pages(): void
    {
        $exam = Exam::factory()->create();
        $student = Student::factory()->create();

        $this->get(route('report-cards.index'))
            ->assertRedirect(route('login'));

        $this->get(route('report-cards.show', [$exam, $student]))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_report_card_index(): void
    {
        $user = $this->principalUser();

        $response = $this->actingAs($user)->get(route('report-cards.index'));

        $response->assertOk();
        $response->assertSee('Report Cards');
        $response->assertSee('Select an exam to begin');
    }

    public function test_exam_filter_shows_students_with_report_card_summaries(): void
    {
        $user = $this->principalUser();
        $academicYear = AcademicYear::factory()->create();
        $exam = Exam::factory()->create(['academic_year_id' => $academicYear->id, 'name' => 'Midterm Exam']);
        $grade = Grade::factory()->create(['name' => 'Grade 7']);
        $section = Section::factory()->create(['grade_id' => $grade->id, 'name' => 'Section A']);
        $student = Student::factory()->create([
            'first_name' => 'Aye',
            'last_name' => 'Chan',
        ]);
        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
        $math = Subject::factory()->create(['name' => 'Mathematics']);
        $english = Subject::factory()->create(['name' => 'English']);

        Mark::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $math->id,
            'score' => 80,
            'max_score' => 100,
        ]);

        Mark::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $english->id,
            'score' => 70,
            'max_score' => 100,
        ]);

        $response = $this->actingAs($user)->get(route('report-cards.index', ['exam_id' => $exam->id]));

        $response->assertOk();
        $response->assertSee('Midterm Exam');
        $response->assertSee('Aye Chan');
        $response->assertSeeInOrder(['150.00', '200.00']);
        $response->assertSee('75.00%');
    }

    public function test_authenticated_users_can_open_a_student_report_card(): void
    {
        $user = $this->principalUser();
        $academicYear = AcademicYear::factory()->create([
            'name' => '2098-2099',
            'start_date' => '2098-06-01',
            'end_date' => '2099-03-31',
        ]);
        $exam = Exam::factory()->create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Final Exam',
            'term' => 'Term 1',
        ]);
        $grade = Grade::factory()->create(['name' => 'Grade 8']);
        $section = Section::factory()->create(['grade_id' => $grade->id, 'name' => 'Blue']);
        $student = Student::factory()->create([
            'first_name' => 'Su',
            'last_name' => 'Mon',
            'admission_no' => 'STU-001',
        ]);
        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrollment_date' => '2098-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
        $subject = Subject::factory()->create(['name' => 'Science']);

        Mark::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'score' => 88,
            'max_score' => 100,
            'grade_letter' => 'A',
            'status' => 'published',
            'remarks' => 'Excellent work',
        ]);

        $response = $this->actingAs($user)->get(route('report-cards.show', [$exam, $student]));

        $response->assertOk();
        $response->assertSee('Su Mon');
        $response->assertSee('Final Exam');
        $response->assertSee('Science');
        $response->assertSee('88.00 / 100.00');
        $response->assertSee('88.00%');
        $response->assertSee('Excellent work');
    }

    public function test_report_card_uses_exam_year_enrollment_even_after_student_is_promoted(): void
    {
        $user = $this->principalUser();
        $examYear = AcademicYear::factory()->create([
            'name' => 'RC-2025-2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-03-31',
        ]);
        $nextYear = AcademicYear::factory()->create([
            'name' => 'RC-2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
        ]);
        $exam = Exam::factory()->create([
            'academic_year_id' => $examYear->id,
            'name' => 'Promotion Exam',
        ]);
        $oldGrade = Grade::factory()->create(['name' => 'Grade 5']);
        $oldSection = Section::factory()->create(['grade_id' => $oldGrade->id, 'name' => 'A']);
        $newGrade = Grade::factory()->create(['name' => 'Grade 6']);
        $newSection = Section::factory()->create(['grade_id' => $newGrade->id, 'name' => 'B']);
        $student = Student::factory()->create([
            'first_name' => 'Mya',
            'last_name' => 'Oo',
        ]);
        $subject = Subject::factory()->create(['name' => 'Mathematics']);

        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $examYear->id,
            'grade_id' => $oldGrade->id,
            'section_id' => $oldSection->id,
            'enrollment_date' => '2025-06-01',
            'status' => Enrollment::STATUS_COMPLETED,
        ]);
        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $nextYear->id,
            'grade_id' => $newGrade->id,
            'section_id' => $newSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
        Mark::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'score' => 92,
            'max_score' => 100,
        ]);

        $indexResponse = $this->actingAs($user)->get(route('report-cards.index', ['exam_id' => $exam->id]));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Mya Oo');
        $indexResponse->assertSee('Grade 5 / A');
        $indexResponse->assertDontSee('Grade 6 / B');

        $showResponse = $this->actingAs($user)->get(route('report-cards.show', [$exam, $student]));
        $showResponse->assertOk();
        $showResponse->assertSee('Grade 5');
        $showResponse->assertSee('A');
        $showResponse->assertDontSee('Grade 6');
        $showResponse->assertSee('92.00 / 100.00');
    }

    public function test_report_card_cannot_be_opened_for_student_from_another_academic_year(): void
    {
        $user = $this->principalUser();
        $examYear = AcademicYear::factory()->create();
        $studentYear = AcademicYear::factory()->create();
        $exam = Exam::factory()->create(['academic_year_id' => $examYear->id]);
        $student = Student::factory()->create();
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);
        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $studentYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)
            ->get(route('report-cards.show', [$exam, $student]))
            ->assertNotFound();
    }

    public function test_principal_can_view_report_cards_without_marks_permission(): void
    {
        $principal = $this->principalUser();
        $academicYear = AcademicYear::factory()->create();
        $exam = Exam::factory()->create(['academic_year_id' => $academicYear->id]);
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);
        $student = Student::factory()->create();
        $subject = Subject::factory()->create();

        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        Mark::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
        ]);

        $this->actingAs($principal)
            ->get(route('report-cards.index', ['exam_id' => $exam->id]))
            ->assertOk();

        $this->actingAs($principal)
            ->get(route('marks.index'))
            ->assertForbidden();
    }

    public function test_teacher_only_sees_report_cards_for_assigned_sections(): void
    {
        $academicYear = AcademicYear::factory()->create();
        [$teacher, $assignedSection] = $this->teacherUserForSection($academicYear);
        $otherGrade = Grade::factory()->create();
        $otherSection = Section::factory()->create(['grade_id' => $otherGrade->id]);
        $exam = Exam::factory()->create(['academic_year_id' => $academicYear->id, 'name' => 'Scoped Exam']);
        $subject = Subject::factory()->create();

        $visibleStudent = Student::factory()->create(['first_name' => 'Visible', 'last_name' => 'Student']);
        Enrollment::query()->create([
            'student_id' => $visibleStudent->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $assignedSection->grade_id,
            'section_id' => $assignedSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
        Mark::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $visibleStudent->id,
            'subject_id' => $subject->id,
        ]);

        $hiddenStudent = Student::factory()->create(['first_name' => 'Hidden', 'last_name' => 'Student']);
        Enrollment::query()->create([
            'student_id' => $hiddenStudent->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $otherGrade->id,
            'section_id' => $otherSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
        Mark::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $hiddenStudent->id,
            'subject_id' => $subject->id,
        ]);

        $response = $this->actingAs($teacher)->get(route('report-cards.index', ['exam_id' => $exam->id]));

        $response->assertOk();
        $response->assertSee('Visible Student');
        $response->assertDontSee('Hidden Student');
    }

    public function test_teacher_cannot_open_report_card_for_student_outside_assigned_section(): void
    {
        $academicYear = AcademicYear::factory()->create();
        [$teacher] = $this->teacherUserForSection($academicYear);
        $otherGrade = Grade::factory()->create();
        $otherSection = Section::factory()->create(['grade_id' => $otherGrade->id]);
        $exam = Exam::factory()->create(['academic_year_id' => $academicYear->id]);
        $student = Student::factory()->create();
        $subject = Subject::factory()->create();

        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $otherGrade->id,
            'section_id' => $otherSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
        Mark::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
        ]);

        $this->actingAs($teacher)
            ->get(route('report-cards.show', [$exam, $student]))
            ->assertNotFound();
    }

    private function principalUser(): User
    {
        $user = User::factory()->create();
        $principalRole = Role::query()->where('slug', 'principal')->firstOrFail();
        $user->roles()->sync([$principalRole->id]);

        return $user;
    }

    private function teacherUserForSection(AcademicYear $academicYear): array
    {
        $user = User::factory()->create();
        $teacherRole = Role::query()->where('slug', 'teacher')->firstOrFail();
        $user->roles()->sync([$teacherRole->id]);

        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);
        $staff = Staff::factory()->create(['user_id' => $user->id]);
        $staff->sectionAssignments()->create([
            'academic_year_id' => $academicYear->id,
            'section_id' => $section->id,
        ]);

        return [$user, $section];
    }
}
