<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ExternalExamRegistration;
use App\Models\ExternalExamSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalExamRegistrationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_external_exam_registration_pages(): void
    {
        $this->get(route('external-exam-registrations.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_register_a_student_for_external_exam(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $session = ExternalExamSession::query()->create([
            'academic_year_id' => AcademicYear::factory()->create()->id,
            'name' => 'AMC Junior',
            'exam_body' => 'Mathematics Board',
            'fee_amount' => 85000,
            'status' => ExternalExamSession::STATUS_OPEN,
        ]);

        $response = $this->actingAs($user)->post(route('external-exam-registrations.store'), [
            'student_id' => $student->id,
            'external_exam_session_id' => $session->id,
            'registration_date' => '2026-05-05',
            'status' => ExternalExamRegistration::STATUS_REGISTERED,
            'fee_amount' => 85000,
            'discount_amount' => 5000,
            'candidate_no' => 'AMC-2026-001',
            'score' => null,
            'grade' => null,
            'result_status' => ExternalExamRegistration::RESULT_PENDING,
            'result_remarks' => null,
            'notes' => 'Paid later',
        ]);

        $registration = ExternalExamRegistration::query()->first();

        $response->assertRedirect(route('external-exam-registrations.show', $registration));
        $this->assertNotNull($registration);
        $this->assertSame(80000.0, (float) $registration->total_amount);
    }

    public function test_student_cannot_be_registered_twice_for_same_external_exam_session(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $session = ExternalExamSession::query()->create([
            'academic_year_id' => AcademicYear::factory()->create()->id,
            'name' => 'YLE Flyers',
            'exam_body' => 'Cambridge English',
            'fee_amount' => 185000,
            'status' => ExternalExamSession::STATUS_OPEN,
        ]);

        ExternalExamRegistration::query()->create([
            'student_id' => $student->id,
            'external_exam_session_id' => $session->id,
            'registration_date' => '2026-05-05',
            'status' => ExternalExamRegistration::STATUS_REGISTERED,
            'fee_amount' => 185000,
            'discount_amount' => 0,
            'total_amount' => 185000,
            'result_status' => ExternalExamRegistration::RESULT_PENDING,
        ]);

        $response = $this->actingAs($user)
            ->from(route('external-exam-registrations.create'))
            ->post(route('external-exam-registrations.store'), [
                'student_id' => $student->id,
                'external_exam_session_id' => $session->id,
                'registration_date' => '2026-05-06',
                'status' => ExternalExamRegistration::STATUS_REGISTERED,
                'fee_amount' => 185000,
                'discount_amount' => 0,
                'result_status' => ExternalExamRegistration::RESULT_PENDING,
            ]);

        $response->assertRedirect(route('external-exam-registrations.create'));
        $response->assertSessionHasErrors('external_exam_session_id');
    }

    public function test_cancelled_external_exam_registration_allows_new_registration(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $session = ExternalExamSession::query()->create([
            'academic_year_id' => AcademicYear::factory()->create()->id,
            'name' => 'YLE Flyers',
            'exam_body' => 'Cambridge English',
            'fee_amount' => 185000,
            'status' => ExternalExamSession::STATUS_OPEN,
        ]);

        $registration = ExternalExamRegistration::query()->create([
            'student_id' => $student->id,
            'external_exam_session_id' => $session->id,
            'registration_date' => '2026-05-05',
            'status' => ExternalExamRegistration::STATUS_REGISTERED,
            'fee_amount' => 185000,
            'discount_amount' => 0,
            'total_amount' => 185000,
            'result_status' => ExternalExamRegistration::RESULT_PENDING,
        ]);

        $this->actingAs($user)
            ->delete(route('external-exam-registrations.destroy', $registration))
            ->assertRedirect(route('external-exam-registrations.index'));

        $this->assertDatabaseHas('external_exam_registrations', [
            'id' => $registration->id,
            'status' => ExternalExamRegistration::STATUS_CANCELLED,
        ]);

        $response = $this->actingAs($user)->post(route('external-exam-registrations.store'), [
            'student_id' => $student->id,
            'external_exam_session_id' => $session->id,
            'registration_date' => '2026-05-06',
            'status' => ExternalExamRegistration::STATUS_REGISTERED,
            'fee_amount' => 185000,
            'discount_amount' => 0,
            'result_status' => ExternalExamRegistration::RESULT_PENDING,
        ]);

        $newRegistration = ExternalExamRegistration::query()
            ->where('status', ExternalExamRegistration::STATUS_REGISTERED)
            ->latest('id')
            ->first();

        $response->assertRedirect(route('external-exam-registrations.show', $newRegistration));
        $this->assertNotNull($newRegistration);
    }
}
