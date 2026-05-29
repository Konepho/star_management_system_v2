<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $contactNumber = fake()->optional()->phoneNumber();

        return [
            'admission_no' => 'STU-' . fake()->unique()->numberBetween(1000, 9999),
            'name_mm' => fake()->name(),
            'name_en' => trim($firstName . ' ' . $lastName),
            'preferred_name' => fake()->optional()->firstName(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'student_type' => fake()->randomElement(['new', 'old']),
            'previous_school_name' => fake()->optional()->company(),
            'date_of_birth' => fake()->dateTimeBetween('-18 years', '-5 years')->format('Y-m-d'),
            'admission_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'email' => fake()->optional()->safeEmail(),
            'contact_number' => $contactNumber,
            'emergency_contact_number' => fake()->optional()->phoneNumber(),
            'phone' => $contactNumber,
            'address' => fake()->optional()->address(),
            'status' => fake()->randomElement(['active', 'inactive', 'graduated', 'transferred']),
        ];
    }
}
