<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Mark;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_mark_pages(): void
    {
        $response = $this->get(route('marks.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_mark_list(): void
    {
        $user = User::factory()->create();
        Mark::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('marks.index'));

        $response->assertOk();
        $response->assertSee('Marks');
    }

    public function test_mark_create_page_shows_type_to_search_student_helper(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('marks.create'));

        $response->assertOk();
        $response->assertSee('Type student name or admission no');
    }

    public function test_authenticated_users_can_create_a_mark_record(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $exam = Exam::factory()->create(['academic_year_id' => $academicYear->id]);
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);
        $student = Student::factory()->create();
        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
        $subject = Subject::factory()->create();

        $response = $this->actingAs($user)->post(route('marks.store'), [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'score' => 78,
            'max_score' => 100,
            'grade_letter' => 'B+',
            'status' => 'published',
            'remarks' => 'Good performance',
        ]);

        $response->assertRedirect(route('marks.index'));

        $this->assertDatabaseHas('marks', [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'score' => 78,
            'grade_letter' => 'B+',
        ]);
    }

    public function test_mark_entry_prevents_duplicate_exam_student_subject_records(): void
    {
        $user = User::factory()->create();
        $mark = Mark::factory()->create();

        $response = $this->actingAs($user)->from(route('marks.create'))->post(route('marks.store'), [
            'exam_id' => $mark->exam_id,
            'student_id' => $mark->student_id,
            'subject_id' => $mark->subject_id,
            'score' => 80,
            'max_score' => 100,
            'grade_letter' => 'A',
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('marks.create'));
        $response->assertSessionHasErrors('subject_id');
    }

    public function test_student_must_belong_to_exam_academic_year(): void
    {
        $user = User::factory()->create();
        $examYear = AcademicYear::factory()->create();
        $studentYear = AcademicYear::factory()->create();
        $exam = Exam::factory()->create(['academic_year_id' => $examYear->id]);
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);
        $student = Student::factory()->create();
        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $studentYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
        $subject = Subject::factory()->create();

        $response = $this->actingAs($user)->from(route('marks.create'))->post(route('marks.store'), [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'score' => 50,
            'max_score' => 100,
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('marks.create'));
        $response->assertSessionHasErrors('student_id');
    }

    public function test_score_cannot_exceed_max_score(): void
    {
        $user = User::factory()->create();
        $mark = Mark::factory()->make();

        $response = $this->actingAs($user)->from(route('marks.create'))->post(route('marks.store'), [
            'exam_id' => $mark->exam_id,
            'student_id' => $mark->student_id,
            'subject_id' => $mark->subject_id,
            'score' => 110,
            'max_score' => 100,
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('marks.create'));
        $response->assertSessionHasErrors('score');
    }

    public function test_authenticated_users_can_update_a_mark_record(): void
    {
        $user = User::factory()->create();
        $mark = Mark::factory()->create();

        $response = $this->actingAs($user)->patch(route('marks.update', $mark), [
            'exam_id' => $mark->exam_id,
            'student_id' => $mark->student_id,
            'subject_id' => $mark->subject_id,
            'score' => 85,
            'max_score' => 100,
            'grade_letter' => 'A',
            'status' => 'reviewed',
            'remarks' => 'Improved result',
        ]);

        $response->assertRedirect(route('marks.index'));

        $this->assertDatabaseHas('marks', [
            'id' => $mark->id,
            'score' => 85,
            'grade_letter' => 'A',
            'status' => 'reviewed',
        ]);
    }

    public function test_authenticated_users_can_archive_a_mark_record(): void
    {
        $user = User::factory()->create();
        $mark = Mark::factory()->create();

        $response = $this->actingAs($user)->delete(route('marks.destroy', $mark));

        $response->assertRedirect(route('marks.index'));
        $this->assertDatabaseHas('marks', [
            'id' => $mark->id,
        ]);
        $this->assertNotNull($mark->fresh()->archived_at);
    }

    public function test_teacher_cannot_create_mark_for_student_outside_assigned_section(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $teacher = $this->teacherUserForSection($academicYear);
        $exam = Exam::factory()->create(['academic_year_id' => $academicYear->id]);
        $otherGrade = Grade::factory()->create();
        $otherSection = Section::factory()->create(['grade_id' => $otherGrade->id]);
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

        $response = $this->actingAs($teacher)
            ->from(route('marks.create'))
            ->post(route('marks.store'), [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'score' => 70,
                'max_score' => 100,
                'status' => 'draft',
            ]);

        $response->assertRedirect(route('marks.create'));
        $response->assertSessionHasErrors('student_id');
    }

    public function test_teacher_cannot_update_mark_for_student_outside_assigned_section(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $teacher = $this->teacherUserForSection($academicYear);
        $otherGrade = Grade::factory()->create();
        $otherSection = Section::factory()->create(['grade_id' => $otherGrade->id]);
        $student = Student::factory()->create();
        $exam = Exam::factory()->create(['academic_year_id' => $academicYear->id]);
        $subject = Subject::factory()->create();

        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $otherGrade->id,
            'section_id' => $otherSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $mark = Mark::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
        ]);

        $response = $this->actingAs($teacher)
            ->from(route('marks.edit', $mark))
            ->patch(route('marks.update', $mark), [
                'exam_id' => $mark->exam_id,
                'student_id' => $mark->student_id,
                'subject_id' => $mark->subject_id,
                'score' => 88,
                'max_score' => 100,
                'grade_letter' => 'A',
                'status' => 'reviewed',
            ]);

        $response->assertRedirect(route('marks.edit', $mark));
        $response->assertSessionHasErrors('student_id');
    }

    private function teacherUserForSection(AcademicYear $academicYear): User
    {
        $user = User::factory()->create();
        $teacherRole = Role::query()->where('slug', 'teacher')->firstOrFail();
        $user->roles()->sync([$teacherRole->id]);

        $assignedGrade = Grade::factory()->create();
        $assignedSection = Section::factory()->create(['grade_id' => $assignedGrade->id]);
        $staff = Staff::factory()->create(['user_id' => $user->id]);
        $staff->sectionAssignments()->create([
            'academic_year_id' => $academicYear->id,
            'section_id' => $assignedSection->id,
        ]);

        return $user;
    }
}
