<?php

namespace Database\Factories;

use App\Models\FeeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeCategory>
 */
class FeeCategoryFactory extends Factory
{
    protected $model = FeeCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Tuition Fee',
            'Registration Fee',
            'Exam Fee',
            'Transport Fee',
            'Library Fee',
        ]) . ' ' . fake()->unique()->numerify('##');

        return [
            'name' => $name,
            'code' => strtoupper(fake()->unique()->bothify('FEE-##??')),
            'type' => fake()->randomElement(['mandatory', 'optional']),
            'allow_discount' => fake()->boolean(80),
            'status' => fake()->randomElement(['active', 'inactive']),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
