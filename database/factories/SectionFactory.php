<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        $code = fake()->unique()->bothify('SEC-##');

        return [
            'grade_id' => Grade::factory(),
            'name' => 'Section ' . str_replace('SEC-', '', $code),
            'code' => $code,
            'capacity' => fake()->numberBetween(25, 50),
            'status' => fake()->randomElement(['active', 'draft', 'closed']),
        ];
    }
}
