<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FeeCategory;
use App\Models\FeePlan;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
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
}
