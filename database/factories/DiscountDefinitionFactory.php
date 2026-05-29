<?php

namespace Database\Factories;

use App\Models\DiscountDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountDefinition>
 */
class DiscountDefinitionFactory extends Factory
{
    protected $model = DiscountDefinition::class;

    public function definition(): array
    {
        $type = fake()->randomElement(array_keys(DiscountDefinition::typeOptions()));

        return [
            'name' => fake()->unique()->randomElement([
                'Sibling Discount',
                'Merit Scholarship',
                'Staff Child Discount',
                'Early Payment Discount',
                'Special Support',
            ]) . ' ' . fake()->unique()->numerify('##'),
            'code' => strtoupper(fake()->unique()->bothify('DISC-##??')),
            'discount_type' => $type,
            'value' => $type === DiscountDefinition::TYPE_PERCENTAGE
                ? fake()->randomFloat(2, 5, 50)
                : fake()->randomFloat(2, 10000, 200000),
            'status' => fake()->randomElement(array_keys(DiscountDefinition::statusOptions())),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
