<?php

namespace Database\Factories;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'staff_no' => 'STF-' . fake()->unique()->numberBetween(1000, 9999),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'department' => fake()->randomElement(['Administration', 'Academics', 'Finance', 'Library']),
            'designation' => fake()->randomElement(['Teacher', 'Coordinator', 'Accountant', 'Librarian']),
            'join_date' => fake()->dateTimeBetween('-8 years', 'now')->format('Y-m-d'),
            'address' => fake()->optional()->address(),
            'status' => fake()->randomElement(['active', 'inactive', 'on-leave', 'resigned']),
        ];
    }
}
