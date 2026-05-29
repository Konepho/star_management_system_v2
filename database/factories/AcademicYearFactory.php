<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(2024, 2055);

        return [
            'name' => sprintf('%d-%d', $startYear, $startYear + 1),
            'start_date' => sprintf('%d-06-01', $startYear),
            'end_date' => sprintf('%d-03-31', $startYear + 1),
            'is_current' => false,
            'status' => fake()->randomElement(['draft', 'active', 'closed']),
        ];
    }
}
