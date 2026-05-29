<?php

namespace Tests\Feature;

use App\Models\PosProductCategory;
use App\Models\PosProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_pos_product_pages(): void
    {
        $this->get(route('pos-products.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_create_a_pos_product(): void
    {
        $user = User::factory()->create();
        $category = PosProductCategory::query()->create([
            'name' => 'Snacks',
            'code' => 'SNK',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('pos-products.store'), [
            'pos_product_category_id' => $category->id,
            'name' => 'Chicken Burger',
            'sku' => 'POS-001',
            'description' => 'Fresh snack item',
            'price' => 3500,
            'stock_quantity' => 25,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('pos-products.index'));
        $this->assertDatabaseHas('pos_products', [
            'name' => 'Chicken Burger',
            'pos_product_category_id' => $category->id,
            'sku' => 'POS-001',
            'price' => 3500,
            'stock_quantity' => 25,
        ]);
    }

    public function test_authenticated_users_can_create_and_deactivate_a_pos_product_category(): void
    {
        $user = User::factory()->create();

        $storeResponse = $this->actingAs($user)->post(route('pos-product-categories.store'), [
            'name' => 'Drinks',
            'code' => 'DRK',
            'description' => 'Cold and hot drinks',
            'status' => 'active',
        ]);

        $category = PosProductCategory::query()->firstOrFail();

        $storeResponse->assertRedirect(route('pos-product-categories.index'));
        $this->assertDatabaseHas('pos_product_categories', [
            'id' => $category->id,
            'name' => 'Drinks',
            'status' => 'active',
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('pos-product-categories.destroy', $category));

        $deleteResponse->assertRedirect(route('pos-product-categories.index'));
        $this->assertDatabaseHas('pos_product_categories', [
            'id' => $category->id,
            'status' => 'inactive',
        ]);
    }

    public function test_authenticated_users_can_deactivate_a_pos_product(): void
    {
        $user = User::factory()->create();
        $product = PosProduct::query()->create([
            'name' => 'Orange Juice',
            'sku' => 'POS-002',
            'price' => 1500,
            'stock_quantity' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->delete(route('pos-products.destroy', $product));

        $response->assertRedirect(route('pos-products.index'));
        $this->assertDatabaseHas('pos_products', [
            'id' => $product->id,
            'status' => 'inactive',
        ]);
    }

    public function test_edit_page_loads_even_when_product_category_is_inactive(): void
    {
        $user = User::factory()->create();
        $category = PosProductCategory::query()->create([
            'name' => 'Seasonal',
            'code' => 'SEA',
            'status' => 'inactive',
        ]);
        $product = PosProduct::query()->create([
            'pos_product_category_id' => $category->id,
            'name' => 'Mango Jelly',
            'sku' => 'POS-003',
            'price' => 1200,
            'stock_quantity' => 12,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('pos-products.edit', $product));

        $response->assertOk();
        $response->assertSee('Mango Jelly');
        $response->assertSee('Seasonal');
    }
}
