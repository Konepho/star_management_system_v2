<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Monthly Test',
            'Midterm Exam',
            'Final Exam',
            'Mock Exam',
            'Promotion Test',
        ]);

        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => $name,
            'code' => strtoupper(str_replace(' ', '-', $name)) . '-' . fake()->numberBetween(1, 9),
            'term' => fake()->randomElement(['Term 1', 'Term 2', 'Term 3']),
            'start_date' => fake()->dateTimeBetween('-1 year', '+1 month')->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+3 months')->format('Y-m-d'),
            'status' => fake()->randomElement(['draft', 'published', 'closed']),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
