<?php

namespace Database\Factories;

use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 12);

        return [
            'name' => 'Grade ' . $number,
            'code' => 'G' . $number,
            'grade_group' => $number <= 5 ? Grade::GROUP_PRIMARY : Grade::GROUP_SECONDARY,
            'sort_order' => $number,
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
