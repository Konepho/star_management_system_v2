<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Mark;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mark>
 */
class MarkFactory extends Factory
{
    protected $model = Mark::class;

    public function definition(): array
    {
        $academicYear = AcademicYear::factory()->create();
        $exam = Exam::factory()->create(['academic_year_id' => $academicYear->id]);
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);
        $student = Student::factory()->create([
            'status' => 'active',
        ]);
        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrollment_date' => now()->toDateString(),
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
        $subject = Subject::factory()->create();
        $max = fake()->randomElement([50, 100]);
        $score = fake()->numberBetween(0, $max);

        return [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'score' => $score,
            'max_score' => $max,
            'grade_letter' => fake()->randomElement(['A', 'B+', 'B', 'C', 'D']),
            'status' => fake()->randomElement(['draft', 'published', 'reviewed']),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
