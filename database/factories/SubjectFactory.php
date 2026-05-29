<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'English',
            'Mathematics',
            'Science',
            'History',
            'Geography',
            'Biology',
            'Chemistry',
            'Physics',
        ]);

        return [
            'name' => $name,
            'code' => strtoupper(substr($name, 0, 4)) . fake()->numberBetween(1, 9),
            'description' => fake()->optional()->sentence(),
            'is_core' => fake()->boolean(70),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }
}
