<?php

namespace Database\Factories;

use App\Models\DiscountDefinition;
use App\Models\Student;
use App\Models\StudentDiscount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentDiscount>
 */
class StudentDiscountFactory extends Factory
{
    protected $model = StudentDiscount::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'student_id' => Student::factory(),
            'discount_definition_id' => DiscountDefinition::factory(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => fake()->optional()->dateTimeBetween($startDate, '+6 months')?->format('Y-m-d'),
            'status' => fake()->randomElement(array_keys(StudentDiscount::statusOptions())),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
