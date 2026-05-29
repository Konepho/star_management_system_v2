<?php

namespace Tests\Feature;

use App\Models\FeeCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_fee_category_pages(): void
    {
        $this->get(route('fee-categories.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_fee_category_list(): void
    {
        $user = User::factory()->create();
        FeeCategory::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('fee-categories.index'));

        $response->assertOk();
        $response->assertSee('Fee Categories');
    }

    public function test_authenticated_users_can_create_a_fee_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('fee-categories.store'), [
            'name' => 'Tuition Fee',
            'code' => 'TUITION',
            'type' => 'mandatory',
            'allow_discount' => '1',
            'status' => 'active',
            'description' => 'Monthly tuition',
        ]);

        $response->assertRedirect(route('fee-categories.index'));

        $this->assertDatabaseHas('fee_categories', [
            'name' => 'Tuition Fee',
            'code' => 'TUITION',
            'allow_discount' => true,
        ]);
    }

    public function test_authenticated_users_can_create_a_non_discountable_material_fee_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('fee-categories.store'), [
            'name' => 'School Uniform',
            'code' => 'UNIFORM',
            'type' => 'mandatory',
            'status' => 'active',
            'description' => 'Uniform set for students',
        ]);

        $response->assertRedirect(route('fee-categories.index'));

        $this->assertDatabaseHas('fee_categories', [
            'name' => 'School Uniform',
            'allow_discount' => false,
        ]);
    }

    public function test_fee_category_code_must_be_unique(): void
    {
        $user = User::factory()->create();
        FeeCategory::factory()->create(['code' => 'REG']);

        $response = $this->actingAs($user)
            ->from(route('fee-categories.create'))
            ->post(route('fee-categories.store'), [
                'name' => 'Registration Fee',
                'code' => 'REG',
                'type' => 'mandatory',
                'allow_discount' => '1',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('fee-categories.create'));
        $response->assertSessionHasErrors('code');
    }

    public function test_authenticated_users_can_update_a_fee_category(): void
    {
        $user = User::factory()->create();
        $feeCategory = FeeCategory::factory()->create();

        $response = $this->actingAs($user)->patch(route('fee-categories.update', $feeCategory), [
            'name' => 'Updated Fee',
            'code' => $feeCategory->code,
            'type' => 'optional',
            'allow_discount' => '0',
            'status' => 'inactive',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('fee-categories.index'));

        $this->assertDatabaseHas('fee_categories', [
            'id' => $feeCategory->id,
            'name' => 'Updated Fee',
            'allow_discount' => false,
            'status' => 'inactive',
        ]);
    }

    public function test_authenticated_users_can_deactivate_a_fee_category(): void
    {
        $user = User::factory()->create();
        $feeCategory = FeeCategory::factory()->create();

        $response = $this->actingAs($user)->delete(route('fee-categories.destroy', $feeCategory));

        $response->assertRedirect(route('fee-categories.index'));
        $this->assertDatabaseHas('fee_categories', [
            'id' => $feeCategory->id,
            'status' => 'inactive',
        ]);
    }
}
