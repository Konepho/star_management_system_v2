<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeStructure>
 */
class FeeStructureFactory extends Factory
{
    protected $model = FeeStructure::class;

    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'grade_id' => Grade::factory(),
            'grade_group' => null,
            'fee_category_id' => FeeCategory::factory(),
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'billing_cycle' => fake()->randomElement(['monthly', 'quarterly', 'annual', 'one-time']),
            'is_optional' => fake()->boolean(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
