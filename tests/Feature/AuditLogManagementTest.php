<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\FeeCategory;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_audit_logs_page(): void
    {
        $user = User::factory()->create();
        AuditLog::query()->create([
            'user_id' => $user->id,
            'category' => 'finance',
            'module' => 'student_payments',
            'action' => 'collected',
            'summary' => 'Collected student payment RCPT/TEST/00001.',
        ]);

        $response = $this->actingAs($user)->get(route('audit-logs.index'));

        $response->assertOk();
        $response->assertSee('Audit Logs');
        $response->assertSee('Collected student payment RCPT/TEST/00001.');
    }

    public function test_finance_manager_only_sees_finance_audit_logs(): void
    {
        $user = User::factory()->create();
        $financeManagerRole = Role::query()->where('slug', 'finance_manager')->firstOrFail();
        $user->roles()->sync([$financeManagerRole->id]);

        AuditLog::query()->create([
            'user_id' => $user->id,
            'category' => 'finance',
            'module' => 'student_payments',
            'action' => 'collected',
            'summary' => 'Finance log entry',
        ]);
        AuditLog::query()->create([
            'user_id' => $user->id,
            'category' => 'academic',
            'module' => 'marks',
            'action' => 'updated',
            'summary' => 'Academic log entry',
        ]);

        $response = $this->actingAs($user)->get(route('audit-logs.index'));

        $response->assertOk();
        $response->assertSee('Finance log entry');
        $response->assertDontSee('Academic log entry');
        $response->assertSee('value="finance"', false);
        $response->assertDontSee('value="academic"', false);
        $response->assertDontSee('value="settings"', false);
    }

    public function test_vice_principal_only_sees_academic_audit_logs(): void
    {
        $user = User::factory()->create();
        $vicePrincipalRole = Role::query()->where('slug', 'vice_principal')->firstOrFail();
        $user->roles()->sync([$vicePrincipalRole->id]);

        AuditLog::query()->create([
            'user_id' => $user->id,
            'category' => 'academic',
            'module' => 'marks',
            'action' => 'updated',
            'summary' => 'Academic-only log entry',
        ]);
        AuditLog::query()->create([
            'user_id' => $user->id,
            'category' => 'settings',
            'module' => 'admin_settings',
            'action' => 'updated',
            'summary' => 'Settings log entry',
        ]);

        $response = $this->actingAs($user)->get(route('audit-logs.index'));

        $response->assertOk();
        $response->assertSee('Academic-only log entry');
        $response->assertDontSee('Settings log entry');
        $response->assertSee('value="academic"', false);
        $response->assertDontSee('value="finance"', false);
        $response->assertDontSee('value="settings"', false);
    }

    public function test_updating_admin_settings_creates_settings_audit_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('admin-settings.update'), [
            'invoice_prefix' => 'SMS-INV',
            'invoice_padding' => 6,
            'invoice_reset_scope' => 'global',
            'receipt_prefix' => 'SMS-RCPT',
            'receipt_padding' => 4,
            'receipt_reset_scope' => 'academic_year',
            'school_name' => 'STAR School',
            'school_phone' => '',
            'school_email' => '',
            'school_address' => '',
            'invoice_name_format' => 'preferred_then_english',
            'student_id_card_fields' => ['grade', 'student_id', 'guardian'],
            'staff_id_card_fields' => ['department', 'designation', 'username'],
        ])->assertRedirect(route('admin-settings.edit'));

        $this->assertDatabaseHas('audit_logs', [
            'category' => 'settings',
            'module' => 'admin_settings',
            'action' => 'updated',
            'summary' => 'Updated admin settings.',
        ]);
    }

    public function test_collecting_and_reversing_payment_creates_finance_audit_logs(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoice([
            'status' => StudentInvoice::STATUS_ISSUED,
            'total_amount' => 50000,
        ]);

        $this->actingAs($user)->post(route('student-payments.store'), [
            'student_invoice_id' => $invoice->id,
            'payment_date' => '2026-05-24',
            'amount' => 20000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ])->assertRedirect();

        $payment = StudentPayment::query()->latest('id')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'category' => 'finance',
            'module' => 'student_payments',
            'action' => 'collected',
            'auditable_type' => $payment->getMorphClass(),
            'auditable_id' => $payment->id,
        ]);

        $this->actingAs($user)->delete(route('student-payments.destroy', $payment))
            ->assertRedirect(route('student-payments.index'));

        $this->assertDatabaseHas('audit_logs', [
            'category' => 'finance',
            'module' => 'student_payments',
            'action' => 'reversed',
            'auditable_type' => $payment->getMorphClass(),
            'auditable_id' => $payment->id,
        ]);
    }

    public function test_creating_enrollment_creates_academic_audit_log(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);

        $this->actingAs($user)->post(route('enrollments.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ])->assertRedirect(route('enrollments.index'));

        $enrollment = Enrollment::query()->latest('id')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'category' => 'academic',
            'module' => 'enrollments',
            'action' => 'created',
            'auditable_type' => $enrollment->getMorphClass(),
            'auditable_id' => $enrollment->id,
        ]);
    }

    public function test_archiving_student_creates_audit_log(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create(['status' => 'active']);

        $this->actingAs($user)->delete(route('students.destroy', $student))
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('audit_logs', [
            'category' => 'academic',
            'module' => 'students',
            'action' => 'archived',
            'auditable_type' => $student->getMorphClass(),
            'auditable_id' => $student->id,
        ]);
    }

    public function test_creating_fee_category_creates_setup_audit_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('fee-categories.store'), [
            'name' => 'Transport Fee',
            'code' => 'TRANSPORT',
            'description' => 'Transport-related fee category',
            'type' => 'mandatory',
            'status' => 'active',
            'allow_discount' => false,
            'is_material_fee' => false,
        ])->assertRedirect(route('fee-categories.index'));

        $feeCategory = FeeCategory::query()->where('code', 'TRANSPORT')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'category' => 'finance',
            'module' => 'fee_categories',
            'action' => 'created',
            'auditable_type' => $feeCategory->getMorphClass(),
            'auditable_id' => $feeCategory->id,
        ]);
    }

    private function createInvoice(array $overrides = []): StudentInvoice
    {
        $student = Student::factory()->create();
        $academicYearId = $overrides['academic_year_id'] ?? AcademicYear::factory()->create()->id;
        $invoice = StudentInvoice::query()->create(array_merge([
            'invoice_no' => 'INV-' . fake()->unique()->numerify('####'),
            'student_id' => $student->id,
            'academic_year_id' => $academicYearId,
            'issue_date' => '2026-05-01',
            'due_date' => '2026-05-31',
            'status' => StudentInvoice::STATUS_ISSUED,
            'total_amount' => 50000,
        ], $overrides));

        $category = FeeCategory::factory()->create(['allow_discount' => false]);
        $invoice->items()->create([
            'fee_category_id' => $category->id,
            'description' => 'Standard Fee',
            'billing_cycle' => 'one-time',
            'quantity' => 1,
            'unit_price' => $invoice->total_amount,
            'amount' => $invoice->total_amount,
        ]);

        return $invoice;
    }
}
