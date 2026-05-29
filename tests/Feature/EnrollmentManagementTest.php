<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FeeCategory;
use App\Models\FeePlan;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_enrollment_pages(): void
    {
        $this->get(route('enrollments.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_enrollment_list(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create(['status' => 'active']);
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);

        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->get(route('enrollments.index'));

        $response->assertOk();
        $response->assertSee('Enrollments');
        $response->assertSee($student->full_name);
    }

    public function test_authenticated_users_can_create_an_enrollment(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $nextAcademicYear = AcademicYear::factory()->create([
            'name' => 'ENROLL-2027-2028',
            'start_date' => '2027-06-01',
            'end_date' => '2028-03-31',
            'status' => 'active',
        ]);
        $nextGrade = Grade::factory()->create([
            'name' => 'Grade 11',
            'code' => 'G11',
            'sort_order' => 11,
            'grade_group' => Grade::GROUP_SECONDARY,
        ]);
        $nextSection = Section::factory()->create([
            'grade_id' => $nextGrade->id,
        ]);
        $feeCategory = FeeCategory::factory()->create();
        $feeStructure = FeeStructure::query()->create([
            'academic_year_id' => $nextAcademicYear->id,
            'grade_id' => null,
            'grade_group' => null,
            'fee_category_id' => $feeCategory->id,
            'amount' => 50000,
            'billing_cycle' => 'monthly',
            'is_optional' => false,
            'status' => 'active',
        ]);
        $feePlan = FeePlan::query()->create([
            'academic_year_id' => $nextAcademicYear->id,
            'name' => 'Secondary BASIC',
            'code' => 'SECONDARY-BASIC',
            'grade_group' => Grade::GROUP_SECONDARY,
            'status' => 'active',
        ]);
        $feePlan->feeStructures()->sync([$feeStructure->id]);

        $response = $this->actingAs($user)->post(route('enrollments.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $nextAcademicYear->id,
            'grade_id' => $nextGrade->id,
            'section_id' => $nextSection->id,
            'fee_plan_id' => $feePlan->id,
            'enrollment_date' => '2027-06-05',
            'status' => Enrollment::STATUS_ACTIVE,
            'remarks' => 'Promoted to next academic year',
        ]);

        $response->assertRedirect(route('enrollments.index'));

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'academic_year_id' => $nextAcademicYear->id,
            'grade_id' => $nextGrade->id,
            'section_id' => $nextSection->id,
            'fee_plan_id' => $feePlan->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $student->refresh();
        $currentEnrollment = $student->currentEnrollment();
        $this->assertNotNull($currentEnrollment);
        $this->assertSame($nextAcademicYear->id, $currentEnrollment->academic_year_id);
        $this->assertSame($nextGrade->id, $currentEnrollment->grade_id);
        $this->assertSame($nextSection->id, $currentEnrollment->section_id);
    }

    public function test_newer_active_enrollment_completes_older_active_enrollment(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $currentYear = AcademicYear::factory()->create([
            'name' => 'ENROLL-2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'status' => 'active',
        ]);
        $nextYear = AcademicYear::factory()->create([
            'name' => 'ENROLL-2027-2028',
            'start_date' => '2027-06-01',
            'end_date' => '2028-03-31',
            'status' => 'active',
        ]);
        $grade = Grade::factory()->create();
        $oldSection = Section::factory()->create(['grade_id' => $grade->id]);
        $newSection = Section::factory()->create(['grade_id' => $grade->id]);

        $oldEnrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $currentYear->id,
            'grade_id' => $grade->id,
            'section_id' => $oldSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->post(route('enrollments.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $nextYear->id,
            'grade_id' => $grade->id,
            'section_id' => $newSection->id,
            'enrollment_date' => '2027-06-05',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('enrollments.index'));
        $this->assertDatabaseHas('enrollments', [
            'id' => $oldEnrollment->id,
            'status' => Enrollment::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'academic_year_id' => $nextYear->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
    }

    public function test_older_historical_enrollment_cannot_be_saved_as_active_when_newer_active_exists(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $oldYear = AcademicYear::factory()->create([
            'name' => 'ENROLL-2025-2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-03-31',
            'status' => 'active',
        ]);
        $currentYear = AcademicYear::factory()->create([
            'name' => 'ENROLL-2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'status' => 'active',
        ]);
        $grade = Grade::factory()->create();
        $oldSection = Section::factory()->create(['grade_id' => $grade->id]);
        $currentSection = Section::factory()->create(['grade_id' => $grade->id]);

        $currentEnrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $currentYear->id,
            'grade_id' => $grade->id,
            'section_id' => $currentSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)
            ->from(route('enrollments.create'))
            ->post(route('enrollments.store'), [
                'student_id' => $student->id,
                'academic_year_id' => $oldYear->id,
                'grade_id' => $grade->id,
                'section_id' => $oldSection->id,
                'enrollment_date' => '2025-06-01',
                'status' => Enrollment::STATUS_ACTIVE,
            ]);

        $response->assertRedirect(route('enrollments.create'));
        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('enrollments', [
            'id' => $currentEnrollment->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $student->id,
            'academic_year_id' => $oldYear->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
    }

    public function test_enrollment_create_page_shows_type_to_search_student_helper(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('enrollments.create'));

        $response->assertOk();
        $response->assertSee('Type student name or admission no');
    }

    public function test_student_cannot_have_duplicate_enrollment_in_same_academic_year(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create(['status' => 'active']);
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);

        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrollment_date' => '2026-06-10',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)
            ->from(route('enrollments.create'))
            ->post(route('enrollments.store'), [
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'grade_id' => $grade->id,
                'section_id' => $section->id,
                'enrollment_date' => '2026-06-15',
                'status' => Enrollment::STATUS_ACTIVE,
            ]);

        $response->assertRedirect(route('enrollments.create'));
        $response->assertSessionHasErrors('academic_year_id');
    }

    public function test_authenticated_users_can_update_an_enrollment(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create(['status' => 'active']);
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);
        $enrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
        $newSection = Section::factory()->create([
            'grade_id' => $grade->id,
        ]);

        $response = $this->actingAs($user)->patch(route('enrollments.update', $enrollment), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $newSection->id,
            'enrollment_date' => '2026-06-10',
            'status' => Enrollment::STATUS_ACTIVE,
            'remarks' => 'Transferred section',
        ]);

        $response->assertRedirect(route('enrollments.index'));
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'section_id' => $newSection->id,
            'remarks' => 'Transferred section',
        ]);
    }

    public function test_section_head_only_sees_enrollments_in_assigned_sections(): void
    {
        $academicYear = AcademicYear::factory()->create();
        [$sectionHead, $assignedSection] = $this->sectionHeadUserForSection($academicYear);
        $otherGrade = Grade::factory()->create();
        $otherSection = Section::factory()->create(['grade_id' => $otherGrade->id]);

        $visibleStudent = Student::factory()->create([
            'first_name' => 'Visible',
            'last_name' => 'Student',
            'name_en' => 'Visible Student',
        ]);
        Enrollment::query()->create([
            'student_id' => $visibleStudent->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $assignedSection->grade_id,
            'section_id' => $assignedSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $hiddenStudent = Student::factory()->create([
            'first_name' => 'Hidden',
            'last_name' => 'Student',
            'name_en' => 'Hidden Student',
        ]);
        Enrollment::query()->create([
            'student_id' => $hiddenStudent->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $otherGrade->id,
            'section_id' => $otherSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($sectionHead)->get(route('enrollments.index'));

        $response->assertOk();
        $response->assertSee($visibleStudent->full_name);
        $response->assertDontSee($hiddenStudent->full_name);
    }

    public function test_section_head_cannot_edit_enrollment_outside_assigned_sections(): void
    {
        $academicYear = AcademicYear::factory()->create();
        [$sectionHead] = $this->sectionHeadUserForSection($academicYear);
        $otherGrade = Grade::factory()->create();
        $otherSection = Section::factory()->create(['grade_id' => $otherGrade->id]);
        $student = Student::factory()->create();
        $enrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $otherGrade->id,
            'section_id' => $otherSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $this->actingAs($sectionHead)
            ->get(route('enrollments.edit', $enrollment))
            ->assertForbidden();
    }

    private function sectionHeadUserForSection(AcademicYear $academicYear): array
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', 'section_head')->firstOrFail();
        $user->roles()->sync([$role->id]);

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
