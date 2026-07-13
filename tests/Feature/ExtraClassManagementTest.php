<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ExtraClassCatalog;
use App\Models\ExtraClassOffering;
use App\Models\FeeCategory;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtraClassManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_manage_extra_class_catalogs(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('extra-class-catalogs.store'), [
            'name' => 'YLE Preparation',
            'code' => 'YLE-PREP',
            'grade_group' => null,
            'description' => 'English prep class',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('extra-class-catalogs.index'));
        $this->assertDatabaseHas('extra_class_catalogs', [
            'name' => 'YLE Preparation',
            'code' => 'YLE-PREP',
            'status' => 'active',
        ]);
    }

    public function test_authenticated_users_can_manage_extra_class_offerings(): void
    {
        $user = User::factory()->create();
        $catalog = ExtraClassCatalog::query()->create([
            'name' => 'Math Olympiad',
            'code' => 'MATH-OLY',
            'status' => 'active',
        ]);
        $academicYear = AcademicYear::factory()->create();
        $feeCategory = FeeCategory::factory()->create(['name' => 'Extra Class Fee']);

        $response = $this->actingAs($user)->post(route('extra-class-offerings.store'), [
            'extra_class_catalog_id' => $catalog->id,
            'academic_year_id' => $academicYear->id,
            'fee_category_id' => $feeCategory->id,
            'name' => 'Math Olympiad 2026',
            'fee_amount' => 120000,
            'start_date' => '2026-06-10',
            'end_date' => '2026-09-30',
            'remarks' => 'Yearly fee version',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('extra-class-offerings.index'));
        $this->assertDatabaseHas('extra_class_offerings', [
            'extra_class_catalog_id' => $catalog->id,
            'academic_year_id' => $academicYear->id,
            'fee_amount' => 120000,
            'status' => 'active',
        ]);
    }

    public function test_authenticated_users_can_register_students_for_extra_classes(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create(['status' => Student::STATUS_ACTIVE]);
        $catalog = ExtraClassCatalog::query()->create([
            'name' => 'Abacus Club',
            'code' => 'ABACUS',
            'status' => 'active',
        ]);
        $academicYear = AcademicYear::factory()->create();
        $offering = ExtraClassOffering::query()->create([
            'extra_class_catalog_id' => $catalog->id,
            'academic_year_id' => $academicYear->id,
            'fee_amount' => 45000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('student-extra-class-registrations.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'extra_class_offering_id' => $offering->id,
            'registration_date' => '2026-06-12',
            'status' => 'active',
            'remarks' => 'Joined after school program',
        ]);

        $response->assertRedirect(route('student-extra-class-registrations.index'));
        $this->assertDatabaseHas('student_extra_class_registrations', [
            'student_id' => $student->id,
            'extra_class_offering_id' => $offering->id,
            'status' => 'active',
        ]);
    }
}
