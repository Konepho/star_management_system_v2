<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\DiscountDefinition;
use App\Models\FeeCategory;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\StudentInvoiceDiscount;
use App\Models\StudentInvoiceItem;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentInvoiceDiscountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_apply_discount(): void
    {
        $invoiceItem = $this->createDiscountableInvoiceItem();
        $discountDefinition = DiscountDefinition::factory()->create();

        $this->post(route('student-invoice-discounts.store'), [
            'student_invoice_id' => $invoiceItem->student_invoice_id,
            'student_invoice_item_id' => $invoiceItem->id,
            'discount_definition_id' => $discountDefinition->id,
        ])->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_apply_percentage_discount_to_discountable_item(): void
    {
        $user = User::factory()->create();
        $invoiceItem = $this->createDiscountableInvoiceItem(100000);
        $discountDefinition = DiscountDefinition::factory()->create([
            'discount_type' => StudentInvoiceDiscount::TYPE_PERCENTAGE,
            'value' => 10,
            'status' => 'active',
        ]);
        $invoice = $invoiceItem->invoice;

        $response = $this->actingAs($user)->post(route('student-invoice-discounts.store'), [
            'student_invoice_id' => $invoice->id,
            'student_invoice_item_id' => $invoiceItem->id,
            'discount_definition_id' => $discountDefinition->id,
            'notes' => 'Ten percent support',
        ]);

        $invoice->refresh();

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $this->assertDatabaseHas('student_invoice_discounts', [
            'student_invoice_id' => $invoice->id,
            'student_invoice_item_id' => $invoiceItem->id,
            'discount_definition_id' => $discountDefinition->id,
            'discount_type' => StudentInvoiceDiscount::TYPE_PERCENTAGE,
            'amount' => 10000,
        ]);
        $this->assertEquals(90000.0, (float) $invoice->total_amount);
    }

    public function test_authenticated_users_can_apply_fixed_discount_to_discountable_item(): void
    {
        $user = User::factory()->create();
        $invoiceItem = $this->createDiscountableInvoiceItem(100000);
        $discountDefinition = DiscountDefinition::factory()->create([
            'discount_type' => StudentInvoiceDiscount::TYPE_FIXED,
            'value' => 15000,
            'status' => 'active',
        ]);
        $invoice = $invoiceItem->invoice;

        $response = $this->actingAs($user)->post(route('student-invoice-discounts.store'), [
            'student_invoice_id' => $invoice->id,
            'student_invoice_item_id' => $invoiceItem->id,
            'discount_definition_id' => $discountDefinition->id,
        ]);

        $invoice->refresh();

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $this->assertEquals(85000.0, (float) $invoice->total_amount);
    }

    public function test_discount_cannot_be_applied_to_non_discountable_item(): void
    {
        $user = User::factory()->create();
        $invoiceItem = $this->createNonDiscountableInvoiceItem(50000);
        $discountDefinition = DiscountDefinition::factory()->create([
            'discount_type' => StudentInvoiceDiscount::TYPE_FIXED,
            'value' => 5000,
            'status' => 'active',
        ]);
        $invoice = $invoiceItem->invoice;

        $response = $this->actingAs($user)
            ->from(route('student-invoices.show', $invoice))
            ->post(route('student-invoice-discounts.store'), [
                'student_invoice_id' => $invoice->id,
                'student_invoice_item_id' => $invoiceItem->id,
                'discount_definition_id' => $discountDefinition->id,
            ]);

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $response->assertSessionHasErrors('student_invoice_item_id');
    }

    public function test_discount_cannot_reduce_invoice_below_paid_amount(): void
    {
        $user = User::factory()->create();
        $invoiceItem = $this->createDiscountableInvoiceItem(100000);
        $discountDefinition = DiscountDefinition::factory()->create([
            'discount_type' => StudentInvoiceDiscount::TYPE_FIXED,
            'value' => 10000,
            'status' => 'active',
        ]);
        $invoice = $invoiceItem->invoice;

        StudentPayment::query()->create([
            'receipt_no' => 'RCPT-DISC-0001',
            'student_invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'payment_date' => '2026-04-28',
            'amount' => 95000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        $invoice->load('payments');
        $invoice->refreshPaymentStatus();

        $response = $this->actingAs($user)
            ->from(route('student-invoices.show', $invoice))
            ->post(route('student-invoice-discounts.store'), [
                'student_invoice_id' => $invoice->id,
                'student_invoice_item_id' => $invoiceItem->id,
                'discount_definition_id' => $discountDefinition->id,
            ]);

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $response->assertSessionHasErrors('discount_definition_id');
    }

    public function test_inactive_discount_definition_cannot_be_applied(): void
    {
        $user = User::factory()->create();
        $invoiceItem = $this->createDiscountableInvoiceItem(100000);
        $discountDefinition = DiscountDefinition::factory()->create([
            'discount_type' => StudentInvoiceDiscount::TYPE_FIXED,
            'value' => 5000,
            'status' => 'inactive',
        ]);
        $invoice = $invoiceItem->invoice;

        $response = $this->actingAs($user)
            ->from(route('student-invoices.show', $invoice))
            ->post(route('student-invoice-discounts.store'), [
                'student_invoice_id' => $invoice->id,
                'student_invoice_item_id' => $invoiceItem->id,
                'discount_definition_id' => $discountDefinition->id,
            ]);

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $response->assertSessionHasErrors('discount_definition_id');
    }

    public function test_authenticated_users_can_remove_discount(): void
    {
        $user = User::factory()->create();
        $invoiceItem = $this->createDiscountableInvoiceItem(100000);
        $invoice = $invoiceItem->invoice;
        $discount = StudentInvoiceDiscount::query()->create([
            'student_invoice_id' => $invoice->id,
            'student_invoice_item_id' => $invoiceItem->id,
            'discount_definition_id' => null,
            'discount_type' => StudentInvoiceDiscount::TYPE_FIXED,
            'value' => 5000,
            'amount' => 5000,
            'reason' => 'Test remove',
        ]);

        $invoice->load(['discounts', 'payments', 'items']);
        $invoice->recalculateTotals();

        $response = $this->actingAs($user)->delete(route('student-invoice-discounts.destroy', $discount));

        $invoice->refresh();

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $this->assertModelMissing($discount);
        $this->assertEquals(100000.0, (float) $invoice->total_amount);
    }

    protected function createDiscountableInvoiceItem(float $amount = 100000): StudentInvoiceItem
    {
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $invoice = StudentInvoice::query()->create([
            'invoice_no' => 'INV-DISC-' . fake()->unique()->numerify('####'),
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-28',
            'due_date' => '2026-05-05',
            'status' => 'issued',
            'total_amount' => $amount,
        ]);
        $category = FeeCategory::factory()->create([
            'allow_discount' => true,
        ]);

        return StudentInvoiceItem::query()->create([
            'student_invoice_id' => $invoice->id,
            'fee_category_id' => $category->id,
            'description' => 'Tuition Fee',
            'billing_cycle' => 'monthly',
            'quantity' => 1,
            'unit_price' => $amount,
            'amount' => $amount,
        ]);
    }

    protected function createNonDiscountableInvoiceItem(float $amount = 50000): StudentInvoiceItem
    {
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $invoice = StudentInvoice::query()->create([
            'invoice_no' => 'INV-MAT-' . fake()->unique()->numerify('####'),
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-28',
            'due_date' => '2026-05-05',
            'status' => 'issued',
            'total_amount' => $amount,
        ]);
        $category = FeeCategory::factory()->create([
            'allow_discount' => false,
        ]);

        return StudentInvoiceItem::query()->create([
            'student_invoice_id' => $invoice->id,
            'fee_category_id' => $category->id,
            'description' => 'School Uniform',
            'billing_cycle' => 'one-time',
            'quantity' => 1,
            'unit_price' => $amount,
            'amount' => $amount,
        ]);
    }
}
