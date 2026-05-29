<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeePlan;
use App\Models\FeeStructure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeePlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_fee_plan_pages(): void
    {
        $this->get(route('fee-plans.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_create_a_fee_plan(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $category = FeeCategory::factory()->create();
        $feeStructure = FeeStructure::query()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => null,
            'fee_category_id' => $category->id,
            'amount' => 100000,
            'billing_cycle' => 'monthly',
            'is_optional' => false,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('fee-plans.store'), [
            'academic_year_id' => $academicYear->id,
            'name' => 'Primary BASIC',
            'code' => 'PRIMARY-BASIC',
            'grade_group' => 'primary',
            'status' => 'active',
            'description' => 'Core primary package',
            'fee_structure_ids' => [$feeStructure->id],
        ]);

        $response->assertRedirect(route('fee-plans.index'));

        $this->assertDatabaseHas('fee_plans', [
            'name' => 'Primary BASIC',
            'code' => 'PRIMARY-BASIC',
        ]);

        $feePlan = FeePlan::query()->where('code', 'PRIMARY-BASIC')->firstOrFail();
        $this->assertTrue($feePlan->feeStructures->contains($feeStructure));
    }

    public function test_authenticated_users_can_deactivate_a_fee_plan(): void
    {
        $user = User::factory()->create();
        $feePlan = FeePlan::query()->create([
            'academic_year_id' => AcademicYear::factory()->create()->id,
            'name' => 'Secondary BASIC',
            'code' => 'SECONDARY-BASIC',
            'grade_group' => 'secondary',
            'status' => 'active',
            'description' => 'Test fee plan',
        ]);

        $response = $this->actingAs($user)->delete(route('fee-plans.destroy', $feePlan));

        $response->assertRedirect(route('fee-plans.index'));
        $this->assertDatabaseHas('fee_plans', [
            'id' => $feePlan->id,
            'status' => 'inactive',
        ]);
    }
}
