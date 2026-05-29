<?php

namespace Tests\Feature;

use App\Models\DiscountDefinition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountDefinitionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_discount_definition_pages(): void
    {
        $this->get(route('discount-definitions.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_discount_definition_list(): void
    {
        $user = User::factory()->create();
        DiscountDefinition::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('discount-definitions.index'));

        $response->assertOk();
        $response->assertSee('Discount Definitions');
    }

    public function test_authenticated_users_can_create_a_discount_definition(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('discount-definitions.store'), [
            'name' => 'Sibling Discount',
            'code' => 'SIBLING10',
            'discount_type' => DiscountDefinition::TYPE_PERCENTAGE,
            'value' => 10,
            'status' => 'active',
            'description' => 'Ten percent for siblings',
        ]);

        $response->assertRedirect(route('discount-definitions.index'));

        $this->assertDatabaseHas('discount_definitions', [
            'name' => 'Sibling Discount',
            'code' => 'SIBLING10',
            'discount_type' => DiscountDefinition::TYPE_PERCENTAGE,
        ]);
    }

    public function test_percentage_discount_definition_cannot_exceed_100_percent(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('discount-definitions.create'))
            ->post(route('discount-definitions.store'), [
                'name' => 'Too Much',
                'code' => 'TOOMUCH',
                'discount_type' => DiscountDefinition::TYPE_PERCENTAGE,
                'value' => 110,
                'status' => 'active',
            ]);

        $response->assertRedirect(route('discount-definitions.create'));
        $response->assertSessionHasErrors('value');
    }

    public function test_authenticated_users_can_update_a_discount_definition(): void
    {
        $user = User::factory()->create();
        $discountDefinition = DiscountDefinition::factory()->create();

        $response = $this->actingAs($user)->patch(route('discount-definitions.update', $discountDefinition), [
            'name' => 'Updated Scholarship',
            'code' => $discountDefinition->code,
            'discount_type' => DiscountDefinition::TYPE_FIXED,
            'value' => 50000,
            'status' => 'inactive',
            'description' => 'Updated discount',
        ]);

        $response->assertRedirect(route('discount-definitions.index'));

        $this->assertDatabaseHas('discount_definitions', [
            'id' => $discountDefinition->id,
            'name' => 'Updated Scholarship',
            'discount_type' => DiscountDefinition::TYPE_FIXED,
            'status' => 'inactive',
        ]);
    }

    public function test_authenticated_users_can_deactivate_a_discount_definition(): void
    {
        $user = User::factory()->create();
        $discountDefinition = DiscountDefinition::factory()->create();

        $response = $this->actingAs($user)->delete(route('discount-definitions.destroy', $discountDefinition));

        $response->assertRedirect(route('discount-definitions.index'));
        $this->assertDatabaseHas('discount_definitions', [
            'id' => $discountDefinition->id,
            'status' => 'inactive',
        ]);
    }
}
