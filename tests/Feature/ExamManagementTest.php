<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_exam_pages(): void
    {
        $response = $this->get(route('exams.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_exam_list(): void
    {
        $user = User::factory()->create();
        Exam::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('exams.index'));

        $response->assertOk();
        $response->assertSee('Exams');
    }

    public function test_authenticated_users_can_create_an_exam(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $response = $this->actingAs($user)->post(route('exams.store'), [
            'academic_year_id' => $academicYear->id,
            'name' => 'Midterm Exam',
            'code' => 'MID-2026',
            'term' => 'Term 1',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
            'status' => 'published',
            'remarks' => 'Main midterm assessment',
        ]);

        $response->assertRedirect(route('exams.index'));

        $this->assertDatabaseHas('exams', [
            'name' => 'Midterm Exam',
            'code' => 'MID-2026',
            'academic_year_id' => $academicYear->id,
            'status' => 'published',
        ]);
    }

    public function test_exam_code_must_be_unique(): void
    {
        $user = User::factory()->create();
        Exam::factory()->create(['code' => 'FINAL-2026']);
        $academicYear = AcademicYear::factory()->create();

        $response = $this->actingAs($user)->from(route('exams.create'))->post(route('exams.store'), [
            'academic_year_id' => $academicYear->id,
            'name' => 'Final Duplicate',
            'code' => 'FINAL-2026',
            'term' => 'Term 2',
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('exams.create'));
        $response->assertSessionHasErrors('code');
    }

    public function test_authenticated_users_can_update_an_exam(): void
    {
        $user = User::factory()->create();
        $exam = Exam::factory()->create();

        $response = $this->actingAs($user)->patch(route('exams.update', $exam), [
            'academic_year_id' => $exam->academic_year_id,
            'name' => 'Updated Final Exam',
            'code' => 'FINAL-UPDATED',
            'term' => 'Term 3',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
            'status' => 'closed',
            'remarks' => 'Updated exam notes',
        ]);

        $response->assertRedirect(route('exams.index'));

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'name' => 'Updated Final Exam',
            'code' => 'FINAL-UPDATED',
            'status' => 'closed',
        ]);
    }

    public function test_authenticated_users_can_close_an_exam(): void
    {
        $user = User::factory()->create();
        $exam = Exam::factory()->create();

        $response = $this->actingAs($user)->delete(route('exams.destroy', $exam));

        $response->assertRedirect(route('exams.index'));
        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'status' => 'closed',
        ]);
    }
}
