<?php

namespace Tests\Feature;

use App\Models\DiscountDefinition;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Models\StudentInvoice;
use App\Models\StudentInvoiceDiscount;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_student_payment_pages(): void
    {
        $this->get(route('student-payments.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_payment_list(): void
    {
        $user = User::factory()->create();
        $payment = $this->createPayment();

        $response = $this->actingAs($user)->get(route('student-payments.index'));

        $response->assertOk();
        $response->assertSee($payment->receipt_no);
    }

    public function test_authenticated_users_can_collect_partial_payment(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoice([
            'total_amount' => 100000,
            'status' => 'issued',
        ]);

        $response = $this->actingAs($user)->post(route('student-payments.store'), [
            'student_invoice_id' => $invoice->id,
            'payment_date' => '2026-04-27',
            'amount' => 40000,
            'payment_method' => StudentPayment::METHOD_MMQR,
            'reference_no' => 'MMQR-001',
            'notes' => 'First installment payment',
        ]);

        $payment = StudentPayment::query()->first();
        $invoice->refresh();

        $response->assertRedirect(route('student-payments.show', $payment));
        $this->assertEquals('partial', $invoice->status);
        $this->assertDatabaseHas('student_payments', [
            'student_invoice_id' => $invoice->id,
            'payment_method' => StudentPayment::METHOD_MMQR,
            'amount' => 40000,
        ]);
    }

    public function test_authenticated_users_can_fully_pay_invoice(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoice([
            'total_amount' => 100000,
            'status' => 'issued',
        ]);

        $response = $this->actingAs($user)->post(route('student-payments.store'), [
            'student_invoice_id' => $invoice->id,
            'payment_date' => '2026-04-27',
            'amount' => 100000,
            'payment_method' => StudentPayment::METHOD_KBZPAY,
            'reference_no' => 'KBZ-001',
        ]);

        $payment = StudentPayment::query()->first();
        $invoice->refresh();

        $response->assertRedirect(route('student-payments.show', $payment));
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_receipt_numbers_follow_academic_year_sequence_policy(): void
    {
        $user = User::factory()->create();
        $invoiceOne = $this->createInvoice([
            'status' => 'issued',
            'total_amount' => 100000,
        ]);
        $invoiceOne->academicYear()->update(['name' => '2026-2027']);

        $invoiceTwo = $this->createInvoice([
            'status' => 'issued',
            'total_amount' => 100000,
            'academic_year_id' => $invoiceOne->academic_year_id,
        ]);

        $this->actingAs($user)->post(route('student-payments.store'), [
            'student_invoice_id' => $invoiceOne->id,
            'payment_date' => '2026-04-27',
            'amount' => 50000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        $this->actingAs($user)->post(route('student-payments.store'), [
            'student_invoice_id' => $invoiceTwo->id,
            'payment_date' => '2026-04-27',
            'amount' => 50000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        $receiptNumbers = StudentPayment::query()->orderBy('id')->pluck('receipt_no')->all();

        $this->assertSame('RCPT/2026-2027/00001', $receiptNumbers[0]);
        $this->assertSame('RCPT/2026-2027/00002', $receiptNumbers[1]);
    }

    public function test_payment_cannot_exceed_invoice_balance(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoice([
            'total_amount' => 50000,
            'status' => 'issued',
        ]);

        $response = $this->actingAs($user)
            ->from(route('student-invoices.show', $invoice))
            ->post(route('student-payments.store'), [
                'student_invoice_id' => $invoice->id,
                'payment_date' => '2026-04-27',
                'amount' => 60000,
                'payment_method' => StudentPayment::METHOD_CASH,
            ]);

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $response->assertSessionHasErrors('amount');
    }

    public function test_draft_invoice_cannot_accept_payments(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoice([
            'total_amount' => 50000,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->from(route('student-invoices.show', $invoice))
            ->post(route('student-payments.store'), [
                'student_invoice_id' => $invoice->id,
                'payment_date' => '2026-04-27',
                'amount' => 10000,
                'payment_method' => StudentPayment::METHOD_CASH,
            ]);

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $response->assertSessionHasErrors('student_invoice_id');
    }

    public function test_reversing_payment_recalculates_invoice_status(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoice([
            'total_amount' => 80000,
            'status' => 'issued',
        ]);

        $payment = StudentPayment::query()->create([
            'receipt_no' => 'RCPT-TEST-0001',
            'student_invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'payment_date' => '2026-04-27',
            'amount' => 80000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        $invoice->load('payments');
        $invoice->refreshPaymentStatus();

        $response = $this->actingAs($user)->delete(route('student-payments.destroy', $payment));

        $invoice->refresh();
        $payment->refresh();

        $response->assertRedirect(route('student-payments.index'));
        $this->assertNotNull($payment->reversed_at);
        $this->assertEquals('issued', $invoice->status);
    }

    public function test_payment_receipt_shows_invoice_discount_amount(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoice([
            'total_amount' => 90000,
            'status' => 'partial',
        ]);
        $category = FeeCategory::factory()->create(['allow_discount' => true]);
        $item = $invoice->items()->create([
            'fee_category_id' => $category->id,
            'description' => 'Tuition Fee',
            'billing_cycle' => 'monthly',
            'quantity' => 1,
            'unit_price' => 100000,
            'amount' => 100000,
        ]);
        $discountDefinition = DiscountDefinition::factory()->create([
            'name' => 'Merit Scholarship',
            'discount_type' => DiscountDefinition::TYPE_FIXED,
            'value' => 10000,
            'status' => 'active',
        ]);
        StudentInvoiceDiscount::query()->create([
            'student_invoice_id' => $invoice->id,
            'student_invoice_item_id' => $item->id,
            'discount_definition_id' => $discountDefinition->id,
            'discount_type' => DiscountDefinition::TYPE_FIXED,
            'value' => 10000,
            'amount' => 10000,
            'reason' => $discountDefinition->name,
        ]);
        $payment = StudentPayment::query()->create([
            'receipt_no' => 'RCPT-DISC-SHOW',
            'student_invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'payment_date' => '2026-04-27',
            'amount' => 30000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        $response = $this->actingAs($user)->get(route('student-payments.show', $payment));

        $response->assertOk();
        $response->assertSee('Discount Amount');
        $response->assertSee('10,000.00');
        $response->assertSee('Merit Scholarship');
    }

    public function test_payment_before_end_of_invoice_month_keeps_auto_discount(): void
    {
        $user = User::factory()->create();
        [$invoice, $discountDefinition] = $this->createInvoiceWithAutoStudentDiscount();

        $response = $this->actingAs($user)->post(route('student-payments.store'), [
            'student_invoice_id' => $invoice->id,
            'payment_date' => '2026-04-30',
            'amount' => 20000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        $payment = StudentPayment::query()->latest('id')->first();
        $invoice->refresh();

        $response->assertRedirect(route('student-payments.show', $payment));
        $this->assertEquals('discount_eligible', $invoice->payment_timing_status);
        $this->assertEquals(90000.0, (float) $invoice->total_amount);
        $this->assertDatabaseHas('student_invoice_discounts', [
            'student_invoice_id' => $invoice->id,
            'discount_definition_id' => $discountDefinition->id,
            'amount' => 10000,
        ]);
        $this->assertDatabaseMissing('student_invoice_items', [
            'student_invoice_id' => $invoice->id,
            'description' => 'Late Fee',
        ]);
    }

    public function test_payment_timing_uses_due_date_month_when_due_date_differs_from_issue_date(): void
    {
        $user = User::factory()->create();
        [$invoice, $discountDefinition] = $this->createInvoiceWithAutoStudentDiscount([
            'issue_date' => '2026-04-10',
            'due_date' => '2026-05-31',
        ]);

        $response = $this->actingAs($user)->post(route('student-payments.store'), [
            'student_invoice_id' => $invoice->id,
            'payment_date' => '2026-05-20',
            'amount' => 20000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        $payment = StudentPayment::query()->latest('id')->first();
        $invoice->refresh();

        $response->assertRedirect(route('student-payments.show', $payment));
        $this->assertEquals('discount_eligible', $invoice->payment_timing_status);
        $this->assertEquals(90000.0, (float) $invoice->total_amount);
        $this->assertDatabaseHas('student_invoice_discounts', [
            'student_invoice_id' => $invoice->id,
            'discount_definition_id' => $discountDefinition->id,
            'amount' => 10000,
        ]);
        $this->assertDatabaseMissing('student_invoice_items', [
            'student_invoice_id' => $invoice->id,
            'description' => 'Late Fee',
        ]);
    }

    public function test_payment_in_first_five_days_of_next_month_removes_auto_discount_without_late_fee(): void
    {
        $user = User::factory()->create();
        [$invoice, $discountDefinition] = $this->createInvoiceWithAutoStudentDiscount();

        $response = $this->actingAs($user)->post(route('student-payments.store'), [
            'student_invoice_id' => $invoice->id,
            'payment_date' => '2026-05-03',
            'amount' => 20000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        $payment = StudentPayment::query()->latest('id')->first();
        $invoice->refresh();

        $response->assertRedirect(route('student-payments.show', $payment));
        $this->assertEquals('grace_period', $invoice->payment_timing_status);
        $this->assertEquals(100000.0, (float) $invoice->total_amount);
        $this->assertDatabaseMissing('student_invoice_discounts', [
            'student_invoice_id' => $invoice->id,
            'discount_definition_id' => $discountDefinition->id,
        ]);
        $this->assertDatabaseMissing('student_invoice_items', [
            'student_invoice_id' => $invoice->id,
            'description' => 'Late Fee',
        ]);
    }

    public function test_payment_between_sixth_and_fifteenth_adds_first_late_fee(): void
    {
        $user = User::factory()->create();
        [$invoice, $discountDefinition] = $this->createInvoiceWithAutoStudentDiscount();

        $response = $this->actingAs($user)->post(route('student-payments.store'), [
            'student_invoice_id' => $invoice->id,
            'payment_date' => '2026-05-10',
            'amount' => 20000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        $payment = StudentPayment::query()->latest('id')->first();
        $invoice->refresh();

        $response->assertRedirect(route('student-payments.show', $payment));
        $this->assertEquals('late_fee_level_1', $invoice->payment_timing_status);
        $this->assertEquals(105000.0, (float) $invoice->total_amount);
        $this->assertDatabaseMissing('student_invoice_discounts', [
            'student_invoice_id' => $invoice->id,
            'discount_definition_id' => $discountDefinition->id,
        ]);
        $this->assertDatabaseHas('student_invoice_items', [
            'student_invoice_id' => $invoice->id,
            'description' => 'Late Fee',
            'amount' => 5000,
            'is_system_adjustment' => true,
            'adjustment_code' => 'late_fee_level_1',
        ]);
    }

    public function test_payment_after_fifteenth_adds_second_late_fee(): void
    {
        $user = User::factory()->create();
        [$invoice, $discountDefinition] = $this->createInvoiceWithAutoStudentDiscount();

        $response = $this->actingAs($user)->post(route('student-payments.store'), [
            'student_invoice_id' => $invoice->id,
            'payment_date' => '2026-05-20',
            'amount' => 20000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        $payment = StudentPayment::query()->latest('id')->first();
        $invoice->refresh();

        $response->assertRedirect(route('student-payments.show', $payment));
        $this->assertEquals('late_fee_level_2', $invoice->payment_timing_status);
        $this->assertEquals(115000.0, (float) $invoice->total_amount);
        $this->assertDatabaseMissing('student_invoice_discounts', [
            'student_invoice_id' => $invoice->id,
            'discount_definition_id' => $discountDefinition->id,
        ]);
        $this->assertDatabaseHas('student_invoice_items', [
            'student_invoice_id' => $invoice->id,
            'description' => 'Late Fee',
            'amount' => 15000,
            'is_system_adjustment' => true,
            'adjustment_code' => 'late_fee_level_2',
        ]);
    }

    public function test_reversing_only_payment_resets_invoice_timing_adjustments(): void
    {
        $user = User::factory()->create();
        [$invoice, $discountDefinition] = $this->createInvoiceWithAutoStudentDiscount();

        $this->actingAs($user)->post(route('student-payments.store'), [
            'student_invoice_id' => $invoice->id,
            'payment_date' => '2026-05-20',
            'amount' => 20000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        $payment = StudentPayment::query()->latest('id')->first();
        $deleteResponse = $this->actingAs($user)->delete(route('student-payments.destroy', $payment));

        $invoice->refresh();
        $payment->refresh();

        $deleteResponse->assertRedirect(route('student-payments.index'));
        $this->assertNotNull($payment->reversed_at);
        $this->assertNull($invoice->payment_timing_status);
        $this->assertNull($invoice->payment_timing_locked_on);
        $this->assertEquals(90000.0, (float) $invoice->total_amount);
        $this->assertDatabaseHas('student_invoice_discounts', [
            'student_invoice_id' => $invoice->id,
            'discount_definition_id' => $discountDefinition->id,
            'amount' => 10000,
        ]);
        $this->assertDatabaseMissing('student_invoice_items', [
            'student_invoice_id' => $invoice->id,
            'description' => 'Late Fee',
        ]);
    }

    protected function createInvoice(array $overrides = []): StudentInvoice
    {
        $student = Student::factory()->create();
        $academicYearId = $overrides['academic_year_id'] ?? AcademicYear::factory()->create()->id;
        $invoice = StudentInvoice::query()->create(array_merge([
            'invoice_no' => 'INV-' . fake()->unique()->numerify('####'),
            'student_id' => $student->id,
            'academic_year_id' => $academicYearId,
            'issue_date' => '2026-04-27',
            'due_date' => '2026-05-05',
            'status' => 'issued',
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

    protected function createInvoiceWithAutoStudentDiscount(array $overrides = []): array
    {
        $student = Student::factory()->create();
        $academicYearId = $overrides['academic_year_id'] ?? AcademicYear::factory()->create()->id;
        $invoice = StudentInvoice::query()->create(array_merge([
            'invoice_no' => 'INV-TIMING-' . fake()->unique()->numerify('####'),
            'student_id' => $student->id,
            'academic_year_id' => $academicYearId,
            'issue_date' => '2026-04-10',
            'due_date' => '2026-04-30',
            'status' => 'issued',
            'total_amount' => 0,
        ], $overrides));
        $category = FeeCategory::factory()->create(['allow_discount' => true]);
        $item = $invoice->items()->create([
            'fee_category_id' => $category->id,
            'description' => 'Tuition Fee',
            'billing_cycle' => 'monthly',
            'quantity' => 1,
            'unit_price' => 100000,
            'amount' => 100000,
        ]);
        $discountDefinition = DiscountDefinition::factory()->create([
            'name' => 'Recurring Scholarship',
            'discount_type' => DiscountDefinition::TYPE_PERCENTAGE,
            'value' => 10,
            'status' => 'active',
        ]);
        StudentDiscount::query()->create([
            'student_id' => $student->id,
            'discount_definition_id' => $discountDefinition->id,
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'notes' => 'Timing test scholarship',
        ]);
        StudentInvoiceDiscount::query()->create([
            'student_invoice_id' => $invoice->id,
            'student_invoice_item_id' => $item->id,
            'discount_definition_id' => $discountDefinition->id,
            'discount_type' => DiscountDefinition::TYPE_PERCENTAGE,
            'value' => 10,
            'amount' => 10000,
            'reason' => $discountDefinition->name,
            'notes' => StudentInvoiceDiscount::AUTO_APPLIED_NOTE_PREFIX . ' Timing test scholarship',
        ]);

        $invoice->load(['items', 'discounts', 'payments']);
        $invoice->recalculateTotals();

        return [$invoice, $discountDefinition];
    }

    protected function createPayment(): StudentPayment
    {
        $invoice = $this->createInvoice();

        return StudentPayment::query()->create([
            'receipt_no' => 'RCPT-LIST-0001',
            'student_invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'payment_date' => '2026-04-27',
            'amount' => 25000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);
    }
}
