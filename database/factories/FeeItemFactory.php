<?php

namespace Database\Factories;

use App\Models\FeeCategory;
use App\Models\FeeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeItem>
 */
class FeeItemFactory extends Factory
{
    protected $model = FeeItem::class;

    public function definition(): array
    {
        $itemName = fake()->randomElement([
            'School Uniform',
            'Exercise Book Set',
            'Stationery Pack',
            'PE Shirt',
            'Book Set',
        ]);

        return [
            'fee_category_id' => FeeCategory::factory(),
            'name' => $itemName,
            'code' => strtoupper(fake()->unique()->bothify('ITEM-##??')),
            'variant' => fake()->optional()->randomElement(['Size S', 'Size M', 'Size L', 'Grade 1', 'Grade 2']),
            'price' => fake()->randomFloat(2, 1000, 500000),
            'status' => fake()->randomElement(['active', 'inactive']),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
