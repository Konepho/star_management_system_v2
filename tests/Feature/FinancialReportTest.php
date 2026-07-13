<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\ExtraClassCatalog;
use App\Models\ExtraClassOffering;
use App\Models\FeeCategory;
use App\Models\FeePlan;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentExtraClassRegistration;
use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_financial_report(): void
    {
        $this->get(route('reports.financial'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_financial_report(): void
    {
        $user = User::factory()->create();
        $academicYear = $this->createAcademicYear();

        $response = $this->actingAs($user)->get(route('reports.financial', [
            'academic_year_id' => $academicYear->id,
        ]));

        $response->assertOk();
        $response->assertSee('Financial Report');
        $response->assertSee($academicYear->name);
    }

    public function test_financial_report_projects_income_from_enrolled_students_and_excludes_optional_fees(): void
    {
        $user = User::factory()->create();
        $academicYear = $this->createAcademicYear();
        $grade = $this->createGrade();
        $category = FeeCategory::query()->create([
            'name' => 'Tuition Fee',
            'code' => 'TUITION',
            'type' => 'core',
            'allow_discount' => true,
            'status' => 'active',
        ]);

        $this->createStudent($academicYear, $grade, 'STU-001');
        $this->createStudent($academicYear, $grade, 'STU-002');

        FeeStructure::query()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => null,
            'fee_category_id' => $category->id,
            'amount' => 1000,
            'billing_cycle' => 'monthly',
            'is_optional' => false,
            'status' => 'active',
        ]);

        FeeStructure::query()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'grade_group' => null,
            'fee_category_id' => $category->id,
            'amount' => 5000,
            'billing_cycle' => 'annual',
            'is_optional' => false,
            'status' => 'active',
        ]);

        FeeStructure::query()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => null,
            'fee_category_id' => $category->id,
            'amount' => 300,
            'billing_cycle' => 'monthly',
            'is_optional' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('reports.financial', [
            'academic_year_id' => $academicYear->id,
        ]));

        $response->assertOk();
        $response->assertSee('Projected Academic-Year Income');
        $response->assertSee('30,000.00');
        $response->assertSee($grade->name);
    }

    public function test_financial_report_can_show_month_and_quarter_views_with_actuals(): void
    {
        $user = User::factory()->create();
        $academicYear = $this->createAcademicYear();
        $grade = $this->createGrade();
        $category = FeeCategory::query()->create([
            'name' => 'Tuition Fee',
            'code' => 'TUITION',
            'type' => 'core',
            'allow_discount' => true,
            'status' => 'active',
        ]);

        $student = $this->createStudent($academicYear, $grade, 'STU-003');

        FeeStructure::query()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => null,
            'fee_category_id' => $category->id,
            'amount' => 1000,
            'billing_cycle' => 'monthly',
            'is_optional' => false,
            'status' => 'active',
        ]);

        FeeStructure::query()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'grade_group' => null,
            'fee_category_id' => $category->id,
            'amount' => 5000,
            'billing_cycle' => 'annual',
            'is_optional' => false,
            'status' => 'active',
        ]);

        $invoice = StudentInvoice::query()->create([
            'invoice_no' => 'INV-FIN-0001',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-30',
            'status' => 'partial',
            'total_amount' => 15000,
        ]);

        StudentPayment::query()->create([
            'receipt_no' => 'RCPT-FIN-0001',
            'student_invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'payment_date' => '2026-06-20',
            'amount' => 5000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        $monthResponse = $this->actingAs($user)->get(route('reports.financial', [
            'academic_year_id' => $academicYear->id,
            'period' => 'month',
            'month' => '2026-06',
        ]));

        $monthResponse->assertOk();
        $monthResponse->assertSee('June 2026');
        $monthResponse->assertSee('15,000.00');
        $monthResponse->assertSee('5,000.00');

        $quarterResponse = $this->actingAs($user)->get(route('reports.financial', [
            'academic_year_id' => $academicYear->id,
            'period' => 'quarter',
            'quarter' => 'q1',
        ]));

        $quarterResponse->assertOk();
        $quarterResponse->assertSee('Quarter 1 (Jun - Aug)');
        $quarterResponse->assertSee('8,000.00');
    }

    public function test_financial_report_prefers_fee_plan_structures_over_generic_matching(): void
    {
        $user = User::factory()->create();
        $academicYear = $this->createAcademicYear();
        $grade = $this->createGrade();
        $student = $this->createStudent($academicYear, $grade, 'STU-010');
        $category = FeeCategory::query()->create([
            'name' => 'Tuition Fee',
            'code' => 'TUITION',
            'type' => 'core',
            'allow_discount' => true,
            'status' => 'active',
        ]);

        $genericStructure = FeeStructure::query()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => null,
            'fee_category_id' => $category->id,
            'amount' => 1000,
            'billing_cycle' => 'monthly',
            'is_optional' => false,
            'status' => 'active',
        ]);

        $planStructure = FeeStructure::query()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => null,
            'fee_category_id' => $category->id,
            'amount' => 2000,
            'billing_cycle' => 'monthly',
            'is_optional' => false,
            'status' => 'active',
        ]);

        $feePlan = FeePlan::query()->create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Primary BASIC',
            'code' => 'PRIMARY-BASIC',
            'grade_group' => $grade->grade_group,
            'status' => 'active',
        ]);
        $feePlan->feeStructures()->sync([$planStructure->id]);

        $student->enrollments()->where('academic_year_id', $academicYear->id)->update([
            'fee_plan_id' => $feePlan->id,
        ]);

        $response = $this->actingAs($user)->get(route('reports.financial', [
            'academic_year_id' => $academicYear->id,
        ]));

        $response->assertOk();
        $response->assertSee('20,000.00');
        $response->assertDontSee('10,000.00');

        $this->assertNotNull($genericStructure);
    }

    public function test_financial_report_projection_includes_active_extra_class_registrations(): void
    {
        $user = User::factory()->create();
        $academicYear = $this->createAcademicYear();
        $grade = $this->createGrade();
        $student = $this->createStudent($academicYear, $grade, 'STU-EXTRA-001');

        $catalog = ExtraClassCatalog::query()->create([
            'name' => 'YLE Preparation',
            'code' => 'YLE-PREP',
            'status' => 'active',
        ]);
        $offering = ExtraClassOffering::query()->create([
            'extra_class_catalog_id' => $catalog->id,
            'academic_year_id' => $academicYear->id,
            'fee_amount' => 7500,
            'status' => 'active',
        ]);
        StudentExtraClassRegistration::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'extra_class_offering_id' => $offering->id,
            'registration_date' => '2026-06-12',
            'status' => StudentExtraClassRegistration::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->get(route('reports.financial', [
            'academic_year_id' => $academicYear->id,
        ]));

        $response->assertOk();
        $response->assertSee('7,500.00');
        $response->assertSee('extra class registrations', false);
    }

    public function test_financial_report_excludes_reversed_payments_from_collected_totals(): void
    {
        $user = User::factory()->create();
        $academicYear = $this->createAcademicYear();
        $grade = $this->createGrade();
        $student = $this->createStudent($academicYear, $grade, 'STU-REV-001');

        $invoice = StudentInvoice::query()->create([
            'invoice_no' => 'INV-FIN-REV-001',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-30',
            'status' => StudentInvoice::STATUS_ISSUED,
            'total_amount' => 10000,
        ]);

        StudentPayment::query()->create([
            'receipt_no' => 'RCPT-FIN-REV-001',
            'student_invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'payment_date' => '2026-06-20',
            'amount' => 4000,
            'payment_method' => StudentPayment::METHOD_CASH,
            'reversed_at' => now(),
            'reversal_reason' => 'Payment entered by mistake.',
        ]);

        $response = $this->actingAs($user)->get(route('reports.financial', [
            'academic_year_id' => $academicYear->id,
            'period' => 'month',
            'month' => '2026-06',
        ]));

        $response->assertOk();
        $response->assertViewHas('collectedForPeriod', 0.0);
        $response->assertViewHas('outstandingForAcademicYear', 10000.0);
    }

    public function test_financial_report_excludes_draft_cancelled_and_void_invoices_from_actual_totals(): void
    {
        $user = User::factory()->create();
        $academicYear = $this->createAcademicYear();
        $grade = $this->createGrade();
        $student = $this->createStudent($academicYear, $grade, 'STU-INV-001');

        StudentInvoice::query()->create([
            'invoice_no' => 'INV-FIN-DRAFT',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-10',
            'due_date' => '2026-06-30',
            'status' => StudentInvoice::STATUS_DRAFT,
            'total_amount' => 1000,
        ]);

        StudentInvoice::query()->create([
            'invoice_no' => 'INV-FIN-CANCEL',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-11',
            'due_date' => '2026-06-30',
            'status' => StudentInvoice::STATUS_CANCELLED,
            'total_amount' => 2000,
        ]);

        StudentInvoice::query()->create([
            'invoice_no' => 'INV-FIN-VOID',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-12',
            'due_date' => '2026-06-30',
            'status' => StudentInvoice::STATUS_VOID,
            'total_amount' => 3000,
        ]);

        StudentInvoice::query()->create([
            'invoice_no' => 'INV-FIN-ISSUED',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-13',
            'due_date' => '2026-06-30',
            'status' => StudentInvoice::STATUS_ISSUED,
            'total_amount' => 4000,
        ]);

        $response = $this->actingAs($user)->get(route('reports.financial', [
            'academic_year_id' => $academicYear->id,
            'period' => 'month',
            'month' => '2026-06',
        ]));

        $response->assertOk();
        $response->assertViewHas('billedForPeriod', 4000.0);
        $response->assertViewHas('outstandingForAcademicYear', 4000.0);
    }

    public function test_financial_report_counts_extra_class_only_students_in_summary_and_grade_breakdown(): void
    {
        $user = User::factory()->create();
        $academicYear = $this->createAcademicYear();
        $grade = $this->createGrade();
        $student = $this->createStandaloneStudent($academicYear, $grade, 'STU-EXTRA-ONLY-001');

        $catalog = ExtraClassCatalog::query()->create([
            'name' => 'Abacus Club',
            'code' => 'ABACUS',
            'status' => 'active',
        ]);
        $offering = ExtraClassOffering::query()->create([
            'extra_class_catalog_id' => $catalog->id,
            'academic_year_id' => $academicYear->id,
            'fee_amount' => 6000,
            'status' => 'active',
        ]);
        StudentExtraClassRegistration::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'extra_class_offering_id' => $offering->id,
            'registration_date' => '2026-06-16',
            'status' => StudentExtraClassRegistration::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->get(route('reports.financial', [
            'academic_year_id' => $academicYear->id,
        ]));

        $response->assertOk();
        $response->assertViewHas('studentsCount', 1);
        $response->assertViewHas('academicStudentsCount', 0);
        $response->assertViewHas('extraClassOnlyStudentsCount', 1);
        $response->assertSee('Extra Class Only Student');
        $response->assertViewHas('gradeSummaries', function ($summaries) {
            return $summaries->contains(function (array $summary): bool {
                return $summary['grade_name'] === 'Extra Class Only Student'
                    && $summary['students_count'] === 1
                    && (float) $summary['projected_amount'] === 6000.0;
            });
        });
    }

    public function test_financial_report_shows_projected_income_mix_and_invoice_stream_breakdown(): void
    {
        $user = User::factory()->create();
        $academicYear = $this->createAcademicYear();
        $grade = $this->createGrade();
        $category = FeeCategory::query()->create([
            'name' => 'Tuition Fee',
            'code' => 'TUITION',
            'type' => 'core',
            'allow_discount' => true,
            'status' => 'active',
        ]);

        $student = $this->createStudent($academicYear, $grade, 'STU-MIX-001');
        $extraOnlyStudent = $this->createStandaloneStudent($academicYear, $grade, 'STU-MIX-002');

        FeeStructure::query()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => null,
            'fee_category_id' => $category->id,
            'amount' => 1000,
            'billing_cycle' => 'monthly',
            'is_optional' => false,
            'status' => 'active',
        ]);

        $catalog = ExtraClassCatalog::query()->create([
            'name' => 'Chess Club',
            'code' => 'CHESS',
            'status' => 'active',
        ]);
        $offering = ExtraClassOffering::query()->create([
            'extra_class_catalog_id' => $catalog->id,
            'academic_year_id' => $academicYear->id,
            'fee_amount' => 5000,
            'status' => 'active',
        ]);
        StudentExtraClassRegistration::query()->create([
            'student_id' => $extraOnlyStudent->id,
            'academic_year_id' => $academicYear->id,
            'extra_class_offering_id' => $offering->id,
            'registration_date' => '2026-06-05',
            'status' => StudentExtraClassRegistration::STATUS_ACTIVE,
        ]);

        StudentInvoice::query()->create([
            'invoice_no' => 'INV-FIN-STREAM-ACAD',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'enrollment_id' => $student->enrollments()->first()?->id,
            'issue_date' => '2026-06-10',
            'due_date' => '2026-06-30',
            'status' => StudentInvoice::STATUS_ISSUED,
            'invoice_stream' => StudentInvoice::STREAM_ACADEMIC,
            'total_amount' => 12000,
        ]);

        StudentInvoice::query()->create([
            'invoice_no' => 'INV-FIN-STREAM-EXTRA',
            'student_id' => $extraOnlyStudent->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-11',
            'due_date' => '2026-06-30',
            'status' => StudentInvoice::STATUS_ISSUED,
            'invoice_stream' => StudentInvoice::STREAM_EXTRA_CLASS,
            'total_amount' => 5000,
        ]);

        $response = $this->actingAs($user)->get(route('reports.financial', [
            'academic_year_id' => $academicYear->id,
        ]));

        $response->assertOk();
        $response->assertSee('Student Mix');
        $response->assertSee('Income Mix');
        $response->assertSee('Invoice Stream Snapshot');
        $response->assertViewHas('academicStudentsCount', 1);
        $response->assertViewHas('extraClassOnlyStudentsCount', 1);
        $response->assertViewHas('projectedAcademicAnnualIncome', 10000.0);
        $response->assertViewHas('projectedExtraClassAnnualIncome', 5000.0);
        $response->assertViewHas('streamSummaries', function ($streams) {
            $academic = $streams->firstWhere('key', StudentInvoice::STREAM_ACADEMIC);
            $extra = $streams->firstWhere('key', StudentInvoice::STREAM_EXTRA_CLASS);

            return $academic !== null
                && $extra !== null
                && (float) $academic['billed_amount'] === 12000.0
                && (float) $extra['billed_amount'] === 5000.0;
        });
    }

    public function test_financial_report_shows_actual_cash_collection_timeline_for_real_payment_months(): void
    {
        $user = User::factory()->create();
        $academicYear = $this->createAcademicYear();
        $grade = $this->createGrade();
        $student = $this->createStudent($academicYear, $grade, 'STU-CASH-001');

        $invoice = StudentInvoice::query()->create([
            'invoice_no' => 'INV-FIN-CASH-001',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-15',
            'due_date' => '2026-06-10',
            'status' => StudentInvoice::STATUS_PARTIAL,
            'invoice_stream' => StudentInvoice::STREAM_ACADEMIC,
            'total_amount' => 30000,
        ]);

        StudentPayment::query()->create([
            'receipt_no' => 'RCPT-FIN-CASH-APR',
            'student_invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'payment_date' => '2026-04-20',
            'amount' => 10000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        StudentPayment::query()->create([
            'receipt_no' => 'RCPT-FIN-CASH-JUN',
            'student_invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'payment_date' => '2026-06-12',
            'amount' => 5000,
            'payment_method' => StudentPayment::METHOD_CASH,
        ]);

        $response = $this->actingAs($user)->get(route('reports.financial', [
            'academic_year_id' => $academicYear->id,
        ]));

        $response->assertOk();
        $response->assertSee('Actual Cash Collection Timeline');
        $response->assertSee('Apr 2026');
        $response->assertSee('Jun 2026');
        $response->assertViewHas('cashTimelineData', function ($timeline) {
            return $timeline->contains(fn (array $point): bool => $point['key'] === '2026-04' && (float) $point['collected'] === 10000.0)
                && $timeline->contains(fn (array $point): bool => $point['key'] === '2026-06' && (float) $point['collected'] === 5000.0);
        });
    }

    protected function createAcademicYear(): AcademicYear
    {
        return AcademicYear::query()->create([
            'name' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
            'status' => 'active',
        ]);
    }

    protected function createGrade(): Grade
    {
        return Grade::query()->create([
            'name' => 'Grade 6',
            'code' => 'G6',
            'grade_group' => Grade::GROUP_SECONDARY,
            'sort_order' => 6,
        ]);
    }

    protected function createStudent(AcademicYear $academicYear, Grade $grade, string $admissionNo): Student
    {
        $section = Section::query()->create([
            'grade_id' => $grade->id,
            'name' => 'Section ' . $admissionNo,
            'code' => 'SEC-' . substr($admissionNo, -3),
            'capacity' => 30,
            'status' => 'active',
        ]);

        $student = Student::query()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'admission_no' => $admissionNo,
            'first_name' => 'Student',
            'last_name' => $admissionNo,
            'gender' => 'male',
            'date_of_birth' => '2012-01-15',
            'admission_date' => '2026-06-10',
            'status' => 'active',
        ]);

        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrollment_date' => '2026-06-10',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        return $student;
    }

    protected function createStandaloneStudent(AcademicYear $academicYear, Grade $grade, string $admissionNo): Student
    {
        $section = Section::query()->create([
            'grade_id' => $grade->id,
            'name' => 'Standalone ' . $admissionNo,
            'code' => 'EXT-' . substr($admissionNo, -3),
            'capacity' => 30,
            'status' => 'active',
        ]);

        return Student::query()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'admission_no' => $admissionNo,
            'first_name' => 'Standalone',
            'last_name' => $admissionNo,
            'gender' => 'male',
            'date_of_birth' => '2012-01-15',
            'admission_date' => '2026-06-10',
            'status' => 'active',
        ]);
    }
}
