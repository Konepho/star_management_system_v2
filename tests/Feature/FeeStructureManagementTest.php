<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeStructureManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_fee_structure_pages(): void
    {
        $this->get(route('fee-structures.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_fee_structure_list(): void
    {
        $user = User::factory()->create();
        FeeStructure::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('fee-structures.index'));

        $response->assertOk();
        $response->assertSee('Fee Structures');
    }

    public function test_authenticated_users_can_create_a_fee_structure(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $feeCategory = FeeCategory::factory()->create();

        $response = $this->actingAs($user)->post(route('fee-structures.store'), [
            'academic_year_id' => $academicYear->id,
            'fee_scope' => 'specific',
            'grade_id' => $grade->id,
            'fee_category_id' => $feeCategory->id,
            'amount' => 50000,
            'billing_cycle' => 'monthly',
            'is_optional' => '0',
            'status' => 'active',
            'remarks' => 'Monthly tuition fee',
        ]);

        $response->assertRedirect(route('fee-structures.index'));

        $this->assertDatabaseHas('fee_structures', [
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'fee_category_id' => $feeCategory->id,
            'billing_cycle' => 'monthly',
        ]);
    }

    public function test_authenticated_users_can_create_a_school_wide_one_time_fee_structure(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $feeCategory = FeeCategory::factory()->create([
            'name' => 'Registration Fee',
            'code' => 'REGISTRATION',
        ]);

        $response = $this->actingAs($user)->post(route('fee-structures.store'), [
            'academic_year_id' => $academicYear->id,
            'fee_scope' => 'all',
            'grade_id' => '',
            'grade_group' => '',
            'fee_category_id' => $feeCategory->id,
            'amount' => 30000,
            'billing_cycle' => 'one-time',
            'is_optional' => '0',
            'status' => 'active',
            'remarks' => 'Same for all new students',
        ]);

        $response->assertRedirect(route('fee-structures.index'));

        $this->assertDatabaseHas('fee_structures', [
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => null,
            'fee_category_id' => $feeCategory->id,
            'billing_cycle' => 'one-time',
        ]);
    }

    public function test_authenticated_users_can_create_a_primary_group_fee_structure(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $feeCategory = FeeCategory::factory()->create([
            'name' => 'Tuition Fee',
            'code' => 'TUITION',
        ]);

        $response = $this->actingAs($user)->post(route('fee-structures.store'), [
            'academic_year_id' => $academicYear->id,
            'fee_scope' => 'group',
            'grade_group' => 'primary',
            'grade_id' => '',
            'fee_category_id' => $feeCategory->id,
            'amount' => 80000,
            'billing_cycle' => 'monthly',
            'is_optional' => '0',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('fee-structures.index'));

        $this->assertDatabaseHas('fee_structures', [
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => 'primary',
            'fee_category_id' => $feeCategory->id,
            'billing_cycle' => 'monthly',
        ]);
    }

    public function test_authenticated_users_can_create_a_pearson_igcse_group_fee_structure(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $feeCategory = FeeCategory::factory()->create([
            'name' => 'IGCSE Tuition',
            'code' => 'IGCSE-TUITION',
        ]);

        $response = $this->actingAs($user)->post(route('fee-structures.store'), [
            'academic_year_id' => $academicYear->id,
            'fee_scope' => 'group',
            'grade_group' => Grade::GROUP_PEARSON_IGCSE,
            'grade_id' => '',
            'fee_category_id' => $feeCategory->id,
            'amount' => 150000,
            'billing_cycle' => 'monthly',
            'is_optional' => '0',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('fee-structures.index'));

        $this->assertDatabaseHas('fee_structures', [
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => Grade::GROUP_PEARSON_IGCSE,
            'fee_category_id' => $feeCategory->id,
            'billing_cycle' => 'monthly',
        ]);
    }

    public function test_authenticated_users_can_create_an_installment_fee_structure(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $feeCategory = FeeCategory::factory()->create([
            'name' => 'IGCSE Foundation Class Fee',
            'code' => 'IGF-CLASS',
        ]);

        $response = $this->actingAs($user)->post(route('fee-structures.store'), [
            'academic_year_id' => $academicYear->id,
            'fee_scope' => 'group',
            'grade_group' => Grade::GROUP_PEARSON_IGCSE,
            'grade_id' => '',
            'fee_category_id' => $feeCategory->id,
            'amount' => '',
            'billing_cycle' => 'installment',
            'installments' => [
                ['amount' => 1350000, 'due_date' => '2026-06-01', 'remarks' => 'Installment 1'],
                ['amount' => 1350000, 'due_date' => '2026-09-01', 'remarks' => 'Installment 2'],
            ],
            'is_optional' => '0',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('fee-structures.index'));

        $feeStructure = FeeStructure::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('fee_category_id', $feeCategory->id)
            ->firstOrFail();

        $this->assertSame('installment', $feeStructure->billing_cycle);
        $this->assertEquals(2700000.0, (float) $feeStructure->amount);
        $this->assertDatabaseCount('fee_installments', 2);
        $this->assertDatabaseHas('fee_installments', [
            'fee_structure_id' => $feeStructure->id,
            'installment_no' => 1,
            'amount' => 1350000,
        ]);
    }

    public function test_same_fee_structure_setup_cannot_be_duplicated(): void
    {
        $user = User::factory()->create();
        $feeStructure = FeeStructure::factory()->create([
            'billing_cycle' => 'monthly',
        ]);

        $response = $this->actingAs($user)
            ->from(route('fee-structures.create'))
            ->post(route('fee-structures.store'), [
                'academic_year_id' => $feeStructure->academic_year_id,
                'fee_scope' => 'specific',
                'grade_id' => $feeStructure->grade_id,
                'fee_category_id' => $feeStructure->fee_category_id,
                'amount' => 70000,
                'billing_cycle' => 'monthly',
                'is_optional' => '0',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('fee-structures.create'));
        $response->assertSessionHasErrors();
    }

    public function test_school_wide_fee_structure_cannot_be_duplicated(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $feeCategory = FeeCategory::factory()->create();

        FeeStructure::factory()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => null,
            'fee_category_id' => $feeCategory->id,
            'billing_cycle' => 'one-time',
        ]);

        $response = $this->actingAs($user)
            ->from(route('fee-structures.create'))
            ->post(route('fee-structures.store'), [
                'academic_year_id' => $academicYear->id,
                'fee_scope' => 'all',
                'grade_id' => '',
                'grade_group' => '',
                'fee_category_id' => $feeCategory->id,
                'amount' => 45000,
                'billing_cycle' => 'one-time',
                'is_optional' => '0',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('fee-structures.create'));
        $response->assertSessionHasErrors('fee_category_id');
    }

    public function test_primary_group_fee_structure_cannot_be_duplicated(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $feeCategory = FeeCategory::factory()->create();

        FeeStructure::factory()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => 'primary',
            'fee_category_id' => $feeCategory->id,
            'billing_cycle' => 'monthly',
        ]);

        $response = $this->actingAs($user)
            ->from(route('fee-structures.create'))
            ->post(route('fee-structures.store'), [
                'academic_year_id' => $academicYear->id,
                'fee_scope' => 'group',
                'grade_group' => 'primary',
                'grade_id' => '',
                'fee_category_id' => $feeCategory->id,
                'amount' => 99999,
                'billing_cycle' => 'monthly',
                'is_optional' => '0',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('fee-structures.create'));
        $response->assertSessionHasErrors('fee_category_id');
    }

    public function test_authenticated_users_can_update_a_fee_structure(): void
    {
        $user = User::factory()->create();
        $feeStructure = FeeStructure::factory()->create();

        $response = $this->actingAs($user)->patch(route('fee-structures.update', $feeStructure), [
            'academic_year_id' => $feeStructure->academic_year_id,
            'fee_scope' => 'specific',
            'grade_id' => $feeStructure->grade_id,
            'grade_group' => '',
            'fee_category_id' => $feeStructure->fee_category_id,
            'amount' => 125000,
            'billing_cycle' => $feeStructure->billing_cycle,
            'is_optional' => '1',
            'status' => 'inactive',
            'remarks' => 'Updated fee setup',
        ]);

        $response->assertRedirect(route('fee-structures.index'));

        $this->assertDatabaseHas('fee_structures', [
            'id' => $feeStructure->id,
            'amount' => 125000,
            'status' => 'inactive',
        ]);
    }

    public function test_updating_from_installment_to_standard_fee_removes_installment_lines(): void
    {
        $user = User::factory()->create();
        $feeStructure = FeeStructure::factory()->create([
            'billing_cycle' => 'installment',
            'amount' => 2700000,
        ]);

        $feeStructure->installments()->createMany([
            ['installment_no' => 1, 'amount' => 1350000, 'due_date' => '2026-06-01'],
            ['installment_no' => 2, 'amount' => 1350000, 'due_date' => '2026-09-01'],
        ]);

        $response = $this->actingAs($user)->patch(route('fee-structures.update', $feeStructure), [
            'academic_year_id' => $feeStructure->academic_year_id,
            'fee_scope' => 'specific',
            'grade_id' => $feeStructure->grade_id,
            'grade_group' => '',
            'fee_category_id' => $feeStructure->fee_category_id,
            'amount' => 1000000,
            'billing_cycle' => 'annual',
            'installments' => [],
            'is_optional' => '1',
            'status' => 'active',
            'remarks' => 'Converted to annual fee',
        ]);

        $response->assertRedirect(route('fee-structures.index'));
        $this->assertDatabaseCount('fee_installments', 0);
    }

    public function test_authenticated_users_can_deactivate_a_fee_structure(): void
    {
        $user = User::factory()->create();
        $feeStructure = FeeStructure::factory()->create();

        $response = $this->actingAs($user)->delete(route('fee-structures.destroy', $feeStructure));

        $response->assertRedirect(route('fee-structures.index'));
        $this->assertDatabaseHas('fee_structures', [
            'id' => $feeStructure->id,
            'status' => 'inactive',
        ]);
    }
}
