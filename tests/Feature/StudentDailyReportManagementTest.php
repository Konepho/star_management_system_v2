<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentDailyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDailyReportManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_student_daily_report_pages(): void
    {
        $this->get(route('student-daily-reports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_student_daily_report_list(): void
    {
        $user = User::factory()->create();
        StudentDailyReport::query()->create([
            'student_id' => Student::factory()->create()->id,
            'reported_by_user_id' => $user->id,
            'report_date' => '2026-05-05',
            'remark' => 'Did not complete homework today.',
        ]);

        $response = $this->actingAs($user)->get(route('student-daily-reports.index'));

        $response->assertOk();
        $response->assertSee('Student Daily Reports');
        $response->assertSee('Did not complete homework today.');
    }

    public function test_create_page_shows_type_to_search_student_helper(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('student-daily-reports.create'));

        $response->assertOk();
        $response->assertSee('Type student name or admission no');
    }

    public function test_authenticated_users_can_create_a_student_daily_report(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($user)->post(route('student-daily-reports.store'), [
            'student_id' => $student->id,
            'report_date' => '2026-05-05',
            'remark' => 'Had a mild headache and rested during reading time.',
        ]);

        $response->assertRedirect(route('student-daily-reports.index'));

        $this->assertDatabaseHas('student_daily_reports', [
            'student_id' => $student->id,
            'reported_by_user_id' => $user->id,
            'remark' => 'Had a mild headache and rested during reading time.',
        ]);
    }

    public function test_authenticated_users_can_update_a_student_daily_report(): void
    {
        $user = User::factory()->create();
        $report = StudentDailyReport::query()->create([
            'student_id' => Student::factory()->create()->id,
            'reported_by_user_id' => $user->id,
            'report_date' => '2026-05-05',
            'remark' => 'Initial remark.',
        ]);

        $response = $this->actingAs($user)->patch(route('student-daily-reports.update', $report), [
            'student_id' => $report->student_id,
            'report_date' => '2026-05-06',
            'remark' => 'Updated follow-up remark.',
        ]);

        $response->assertRedirect(route('student-daily-reports.index'));

        $this->assertDatabaseHas('student_daily_reports', [
            'id' => $report->id,
            'remark' => 'Updated follow-up remark.',
        ]);

        $this->assertSame('2026-05-06', $report->fresh()->report_date?->format('Y-m-d'));
    }

    public function test_authenticated_users_can_archive_a_student_daily_report(): void
    {
        $user = User::factory()->create();
        $report = StudentDailyReport::query()->create([
            'student_id' => Student::factory()->create()->id,
            'reported_by_user_id' => $user->id,
            'report_date' => '2026-05-05',
            'remark' => 'Delete me.',
        ]);

        $response = $this->actingAs($user)->delete(route('student-daily-reports.destroy', $report));

        $response->assertRedirect(route('student-daily-reports.index'));
        $this->assertDatabaseHas('student_daily_reports', [
            'id' => $report->id,
        ]);
        $this->assertNotNull($report->fresh()->archived_at);
    }

    public function test_authenticated_users_can_filter_student_daily_reports(): void
    {
        $user = User::factory()->create();
        $targetStudent = Student::factory()->create([
            'admission_no' => 'STU-1001',
            'name_en' => 'Aye Chan',
        ]);
        $otherStudent = Student::factory()->create([
            'admission_no' => 'STU-1002',
            'name_en' => 'Moe Thiri',
        ]);

        StudentDailyReport::query()->create([
            'student_id' => $targetStudent->id,
            'reported_by_user_id' => $user->id,
            'report_date' => '2026-05-05',
            'remark' => 'Homework completed well.',
        ]);

        StudentDailyReport::query()->create([
            'student_id' => $otherStudent->id,
            'reported_by_user_id' => $user->id,
            'report_date' => '2026-05-06',
            'remark' => 'Felt unwell during class.',
        ]);

        $response = $this->actingAs($user)->get(route('student-daily-reports.index', [
            'student_id' => $targetStudent->id,
            'report_date' => '2026-05-05',
            'search' => 'Homework',
        ]));

        $response->assertOk();
        $response->assertSee('Homework completed well.');
        $response->assertDontSee('Felt unwell during class.');
    }

    public function test_teacher_only_sees_daily_reports_for_assigned_sections(): void
    {
        $academicYear = AcademicYear::factory()->create();
        [$teacher, $assignedSection] = $this->teacherUserForSection($academicYear);
        $otherGrade = Grade::factory()->create();
        $otherSection = Section::factory()->create(['grade_id' => $otherGrade->id]);

        $visibleStudent = Student::factory()->create(['name_en' => 'Visible Student']);
        Enrollment::query()->create([
            'student_id' => $visibleStudent->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $assignedSection->grade_id,
            'section_id' => $assignedSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
        StudentDailyReport::query()->create([
            'student_id' => $visibleStudent->id,
            'reported_by_user_id' => $teacher->id,
            'report_date' => '2026-05-05',
            'remark' => 'Visible report.',
        ]);

        $hiddenStudent = Student::factory()->create(['name_en' => 'Hidden Student']);
        Enrollment::query()->create([
            'student_id' => $hiddenStudent->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $otherGrade->id,
            'section_id' => $otherSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
        StudentDailyReport::query()->create([
            'student_id' => $hiddenStudent->id,
            'reported_by_user_id' => $teacher->id,
            'report_date' => '2026-05-05',
            'remark' => 'Hidden report.',
        ]);

        $response = $this->actingAs($teacher)->get(route('student-daily-reports.index'));

        $response->assertOk();
        $response->assertSee('Visible report.');
        $response->assertDontSee('Hidden report.');
    }

    public function test_teacher_cannot_create_daily_report_for_student_outside_assigned_sections(): void
    {
        $academicYear = AcademicYear::factory()->create();
        [$teacher] = $this->teacherUserForSection($academicYear);
        $otherGrade = Grade::factory()->create();
        $otherSection = Section::factory()->create(['grade_id' => $otherGrade->id]);
        $student = Student::factory()->create();

        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $otherGrade->id,
            'section_id' => $otherSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $this->actingAs($teacher)
            ->post(route('student-daily-reports.store'), [
                'student_id' => $student->id,
                'report_date' => '2026-05-05',
                'remark' => 'Out of scope report.',
            ])
            ->assertForbidden();
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
