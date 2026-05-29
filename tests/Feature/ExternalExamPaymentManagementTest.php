<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ExternalExamPayment;
use App\Models\ExternalExamRegistration;
use App\Models\ExternalExamSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalExamPaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_external_exam_payment_list(): void
    {
        $user = User::factory()->create();
        $payment = $this->createPayment();

        $response = $this->actingAs($user)->get(route('external-exam-payments.index'));

        $response->assertOk();
        $response->assertSee($payment->receipt_no);
        $response->assertSee($payment->registration->student->full_name);
    }

    public function test_authenticated_users_can_view_external_exam_payment_receipt_page(): void
    {
        $user = User::factory()->create();
        $payment = $this->createPayment();

        $response = $this->actingAs($user)->get(route('external-exam-payments.show', $payment));

        $response->assertOk();
        $response->assertSee($payment->receipt_no);
        $response->assertSee('Registration Summary');
    }

    public function test_authenticated_users_can_collect_external_exam_payment(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create(['name' => '2026-2027']);
        $session = ExternalExamSession::query()->create([
            'academic_year_id' => $academicYear->id,
            'name' => 'AMC Senior',
            'exam_body' => 'Math Association',
            'fee_amount' => 100000,
            'status' => ExternalExamSession::STATUS_OPEN,
        ]);
        $registration = ExternalExamRegistration::query()->create([
            'student_id' => Student::factory()->create()->id,
            'external_exam_session_id' => $session->id,
            'registration_date' => '2026-05-05',
            'status' => ExternalExamRegistration::STATUS_REGISTERED,
            'fee_amount' => 100000,
            'discount_amount' => 5000,
            'total_amount' => 95000,
            'result_status' => ExternalExamRegistration::RESULT_PENDING,
        ]);

        $response = $this->actingAs($user)->post(route('external-exam-payments.store'), [
            'external_exam_registration_id' => $registration->id,
            'payment_date' => '2026-05-05',
            'amount' => 50000,
            'payment_method' => ExternalExamPayment::METHOD_MMQR,
            'reference_no' => 'EXAM-MMQR-01',
        ]);

        $payment = ExternalExamPayment::query()->first();

        $response->assertRedirect(route('external-exam-registrations.show', $registration));
        $this->assertNotNull($payment);
        $this->assertSame('EXAM-RCPT/2026-2027/00001', $payment->receipt_no);
        $this->assertSame(45000.0, $registration->fresh()->balance_due);
    }

    public function test_external_exam_payment_cannot_exceed_balance(): void
    {
        $user = User::factory()->create();
        $session = ExternalExamSession::query()->create([
            'academic_year_id' => AcademicYear::factory()->create()->id,
            'name' => 'YLE Starters',
            'exam_body' => 'Cambridge English',
            'fee_amount' => 120000,
            'status' => ExternalExamSession::STATUS_OPEN,
        ]);
        $registration = ExternalExamRegistration::query()->create([
            'student_id' => Student::factory()->create()->id,
            'external_exam_session_id' => $session->id,
            'registration_date' => '2026-05-05',
            'status' => ExternalExamRegistration::STATUS_REGISTERED,
            'fee_amount' => 120000,
            'discount_amount' => 0,
            'total_amount' => 120000,
            'result_status' => ExternalExamRegistration::RESULT_PENDING,
        ]);

        $response = $this->actingAs($user)
            ->from(route('external-exam-registrations.show', $registration))
            ->post(route('external-exam-payments.store'), [
                'external_exam_registration_id' => $registration->id,
                'payment_date' => '2026-05-05',
                'amount' => 150000,
                'payment_method' => ExternalExamPayment::METHOD_CASH,
            ]);

        $response->assertRedirect(route('external-exam-registrations.show', $registration));
        $response->assertSessionHasErrors('amount');
    }

    public function test_reversing_external_exam_payment_keeps_receipt_history_and_restores_balance(): void
    {
        $user = User::factory()->create();
        $payment = $this->createPayment();
        $registration = $payment->registration;

        $response = $this->actingAs($user)->delete(route('external-exam-payments.destroy', $payment));

        $payment->refresh();
        $registration->refresh();

        $response->assertRedirect(route('external-exam-registrations.show', $registration));
        $this->assertNotNull($payment->reversed_at);
        $this->assertSame(0.0, $registration->paid_amount);
        $this->assertSame((float) $registration->total_amount, $registration->balance_due);
    }

    protected function createPayment(): ExternalExamPayment
    {
        $academicYear = AcademicYear::factory()->create(['name' => '2026-2027']);
        $session = ExternalExamSession::query()->create([
            'academic_year_id' => $academicYear->id,
            'name' => 'YLE Starters',
            'exam_body' => 'Cambridge English',
            'fee_amount' => 120000,
            'status' => ExternalExamSession::STATUS_OPEN,
        ]);
        $registration = ExternalExamRegistration::query()->create([
            'student_id' => Student::factory()->create()->id,
            'external_exam_session_id' => $session->id,
            'registration_date' => '2026-05-05',
            'status' => ExternalExamRegistration::STATUS_REGISTERED,
            'fee_amount' => 120000,
            'discount_amount' => 5000,
            'total_amount' => 115000,
            'result_status' => ExternalExamRegistration::RESULT_PENDING,
        ]);

        return ExternalExamPayment::query()->create([
            'receipt_no' => 'EXAM-RCPT/2026-2027/00001',
            'external_exam_registration_id' => $registration->id,
            'payment_date' => '2026-05-05',
            'amount' => 50000,
            'payment_method' => ExternalExamPayment::METHOD_CASH,
            'reference_no' => 'EXAM-CASH-01',
        ]);
    }
}
