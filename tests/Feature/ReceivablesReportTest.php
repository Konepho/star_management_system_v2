<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceivablesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_receivables_report(): void
    {
        $this->get(route('reports.receivables'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_receivables_report(): void
    {
        $user = User::factory()->create();
        $this->createInvoiceWithBalance('INV-REC-0001', now()->format('Y-m-20'), 50000);

        $response = $this->actingAs($user)->get(route('reports.receivables'));

        $response->assertOk();
        $response->assertSee('Receivables Report');
        $response->assertSee('INV-REC-0001');
    }

    public function test_receivables_report_filters_due_invoices_by_month(): void
    {
        $user = User::factory()->create();
        $this->createInvoiceWithBalance('INV-MAY-0001', '2026-05-20', 50000);
        $this->createInvoiceWithBalance('INV-JUN-0001', '2026-06-05', 75000);

        $response = $this->actingAs($user)->get(route('reports.receivables', [
            'view' => 'monthly',
            'year' => 2026,
            'month' => 5,
        ]));

        $response->assertOk();
        $response->assertSee('INV-MAY-0001');
        $response->assertDontSee('INV-JUN-0001');
        $response->assertSee('50,000.00');
    }

    public function test_receivables_report_groups_expected_amounts_by_month_and_year(): void
    {
        $user = User::factory()->create();
        $this->createInvoiceWithBalance('INV-MAY-0001', '2026-05-20', 50000);
        $this->createInvoiceWithBalance('INV-JUN-0001', '2026-06-05', 75000);
        $this->createInvoiceWithBalance('INV-2027-0001', '2027-01-10', 90000);

        $response = $this->actingAs($user)->get(route('reports.receivables', [
            'view' => 'yearly',
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('Overdue Ageing');
        $response->assertSee('Receivables Summary');
    }

    public function test_receivables_report_shows_collected_amounts_for_selected_due_period_invoices(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoiceWithBalance('INV-PAY-0001', '2026-05-20', 50000);

        StudentPayment::query()->create([
            'receipt_no' => 'RCPT-REC-0001',
            'student_invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'payment_date' => '2026-05-12',
            'amount' => 15000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        $response = $this->actingAs($user)->get(route('reports.receivables', [
            'view' => 'monthly',
            'year' => 2026,
            'month' => 5,
        ]));

        $response->assertOk();
        $response->assertSee('Collected on Those Invoices');
        $response->assertSee('Receivables Summary');
        $response->assertSee('15,000.00');
        $response->assertViewHas('periodInvoicedAmount', 50000.0);
        $response->assertViewHas('periodCollectedAmount', 15000.0);
        $response->assertViewHas('periodOutstanding', 35000.0);
    }

    public function test_receivables_report_excludes_reversed_payments_from_period_collection_totals(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoiceWithBalance('INV-REV-0001', '2026-05-20', 50000);

        StudentPayment::query()->create([
            'receipt_no' => 'RCPT-REC-REV-0001',
            'student_invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'payment_date' => '2026-05-12',
            'amount' => 15000,
            'payment_method' => StudentPayment::METHOD_CASH,
            'reversed_at' => now(),
            'reversal_reason' => 'Mistake',
        ]);

        $response = $this->actingAs($user)->get(route('reports.receivables', [
            'view' => 'monthly',
            'year' => 2026,
            'month' => 5,
        ]));

        $response->assertOk();
        $response->assertViewHas('periodCollectedAmount', 0.0);
        $response->assertViewHas('periodOutstanding', 50000.0);
    }

    protected function createInvoiceWithBalance(string $invoiceNo, string $dueDate, float $amount): StudentInvoice
    {
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $invoice = StudentInvoice::query()->create([
            'invoice_no' => $invoiceNo,
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-15',
            'due_date' => $dueDate,
            'status' => 'issued',
            'total_amount' => $amount,
        ]);

        return $invoice;
    }
}
