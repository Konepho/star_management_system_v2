<?php

namespace Tests\Feature;

use App\Models\FeeCategory;
use App\Models\FeeItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeItemManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_fee_item_pages(): void
    {
        $this->get(route('fee-items.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_fee_item_list(): void
    {
        $user = User::factory()->create();
        FeeItem::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('fee-items.index'));

        $response->assertOk();
        $response->assertSee('Fee Items');
    }

    public function test_authenticated_users_can_create_a_fee_item(): void
    {
        $user = User::factory()->create();
        $feeCategory = FeeCategory::factory()->create([
            'name' => 'School Uniform',
            'allow_discount' => false,
        ]);

        $response = $this->actingAs($user)->post(route('fee-items.store'), [
            'fee_category_id' => $feeCategory->id,
            'name' => 'Uniform Shirt',
            'code' => 'UNI-SHIRT-28',
            'variant' => 'Size 28',
            'price' => 25000,
            'status' => 'active',
            'description' => 'Boys uniform shirt',
        ]);

        $response->assertRedirect(route('fee-items.index'));

        $this->assertDatabaseHas('fee_items', [
            'fee_category_id' => $feeCategory->id,
            'name' => 'Uniform Shirt',
            'code' => 'UNI-SHIRT-28',
            'variant' => 'Size 28',
            'price' => 25000,
        ]);
    }

    public function test_fee_item_code_must_be_unique(): void
    {
        $user = User::factory()->create();
        FeeItem::factory()->create(['code' => 'BOOK-G1']);
        $feeCategory = FeeCategory::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('fee-items.create'))
            ->post(route('fee-items.store'), [
                'fee_category_id' => $feeCategory->id,
                'name' => 'Grade 1 Book Set',
                'code' => 'BOOK-G1',
                'variant' => null,
                'price' => 80000,
                'status' => 'active',
            ]);

        $response->assertRedirect(route('fee-items.create'));
        $response->assertSessionHasErrors('code');
    }

    public function test_authenticated_users_can_update_a_fee_item(): void
    {
        $user = User::factory()->create();
        $feeItem = FeeItem::factory()->create();

        $response = $this->actingAs($user)->patch(route('fee-items.update', $feeItem), [
            'fee_category_id' => $feeItem->fee_category_id,
            'name' => 'Updated Fee Item',
            'code' => $feeItem->code,
            'variant' => 'Size M',
            'price' => 32000,
            'status' => 'inactive',
            'description' => 'Updated item description',
        ]);

        $response->assertRedirect(route('fee-items.index'));

        $this->assertDatabaseHas('fee_items', [
            'id' => $feeItem->id,
            'name' => 'Updated Fee Item',
            'variant' => 'Size M',
            'price' => 32000,
            'status' => 'inactive',
        ]);
    }

    public function test_authenticated_users_can_deactivate_a_fee_item(): void
    {
        $user = User::factory()->create();
        $feeItem = FeeItem::factory()->create();

        $response = $this->actingAs($user)->delete(route('fee-items.destroy', $feeItem));

        $response->assertRedirect(route('fee-items.index'));
        $this->assertDatabaseHas('fee_items', [
            'id' => $feeItem->id,
            'status' => 'inactive',
        ]);
    }
}
