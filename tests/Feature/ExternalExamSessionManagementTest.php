<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ExternalExamSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalExamSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_external_exam_session_pages(): void
    {
        $this->get(route('external-exam-sessions.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_create_an_external_exam_session(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $response = $this->actingAs($user)->post(route('external-exam-sessions.store'), [
            'academic_year_id' => $academicYear->id,
            'name' => 'YLE Movers 2026',
            'exam_body' => 'Cambridge English',
            'level' => 'Movers',
            'exam_date' => '2026-10-01',
            'registration_deadline' => '2026-08-15',
            'fee_amount' => 175000,
            'status' => ExternalExamSession::STATUS_OPEN,
            'remarks' => 'Main YLE exam window',
        ]);

        $response->assertRedirect(route('external-exam-sessions.index'));

        $this->assertDatabaseHas('external_exam_sessions', [
            'name' => 'YLE Movers 2026',
            'exam_body' => 'Cambridge English',
            'fee_amount' => 175000,
        ]);
    }

    public function test_authenticated_users_can_cancel_an_external_exam_session(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $session = ExternalExamSession::query()->create([
            'academic_year_id' => $academicYear->id,
            'name' => 'AMC 2026',
            'exam_body' => 'Mathematics Association',
            'level' => 'Junior',
            'exam_date' => '2026-11-10',
            'registration_deadline' => '2026-09-30',
            'fee_amount' => 95000,
            'status' => ExternalExamSession::STATUS_OPEN,
            'remarks' => 'Test session',
        ]);

        $response = $this->actingAs($user)->delete(route('external-exam-sessions.destroy', $session));

        $response->assertRedirect(route('external-exam-sessions.index'));
        $this->assertDatabaseHas('external_exam_sessions', [
            'id' => $session->id,
            'status' => ExternalExamSession::STATUS_CANCELLED,
        ]);
    }
}
