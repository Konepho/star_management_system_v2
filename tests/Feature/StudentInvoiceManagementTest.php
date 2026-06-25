<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\DiscountDefinition;
use App\Models\Enrollment;
use App\Models\FeeCategory;
use App\Models\FeeItem;
use App\Models\FeePlan;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentInvoiceDiscount;
use App\Models\StudentDiscount;
use App\Models\StudentInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentInvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_student_invoice_pages(): void
    {
        $this->get(route('student-invoices.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_student_invoice_list(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        StudentInvoice::query()->create([
            'invoice_no' => 'INV-TEST-0001',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => now()->toDateString(),
            'status' => 'issued',
            'total_amount' => 50000,
        ]);

        $response = $this->actingAs($user)->get(route('student-invoices.index'));

        $response->assertOk();
        $response->assertSee('Student Invoices');
    }

    public function test_authenticated_users_can_search_student_invoices(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $matchingStudent = Student::factory()->create([
            'admission_no' => 'STU-SEARCH-001',
            'name_en' => 'Aye Chan',
        ]);
        $otherStudent = Student::factory()->create([
            'admission_no' => 'STU-OTHER-001',
            'name_en' => 'Moe Thu',
        ]);

        StudentInvoice::query()->create([
            'invoice_no' => 'INV-SEARCH-001',
            'student_id' => $matchingStudent->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => now()->toDateString(),
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_CUSTOM,
            'billing_year_label' => 'April 2026',
            'total_amount' => 50000,
        ]);

        StudentInvoice::query()->create([
            'invoice_no' => 'INV-OTHER-001',
            'student_id' => $otherStudent->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => now()->toDateString(),
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_CUSTOM,
            'billing_year_label' => 'May 2026',
            'total_amount' => 65000,
        ]);

        $response = $this->actingAs($user)->get(route('student-invoices.index', ['search' => 'Aye Chan']));

        $response->assertOk();
        $response->assertSee('INV-SEARCH-001');
        $response->assertDontSee('INV-OTHER-001');
        $response->assertSee('Aye Chan');
        $response->assertDontSee('Moe Thu');
    }

    public function test_authenticated_users_can_view_invoice_create_page_with_enrollment_summary_section(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('student-invoices.create'));

        $response->assertOk();
        $response->assertSee('Enrollment Billing Summary');
        $response->assertSee('Auto-loaded from enrollment');
        $response->assertSee('Type student name or admission no');
    }

    public function test_authenticated_users_can_view_printable_invoice_page(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $invoice = StudentInvoice::query()->create([
            'invoice_no' => 'INV-PRINT-0001',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-05-02',
            'due_date' => '2026-05-10',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_CUSTOM,
            'billing_year_label' => 'Printable Invoice',
            'total_amount' => 50000,
        ]);
        AppSetting::setValue('invoice.school_name', 'STAR International School');
        AppSetting::setValue('invoice.school_phone', '09-987654321');
        AppSetting::setValue('invoice.school_email', 'finance@star.edu.mm');
        AppSetting::setValue('invoice.school_address', 'Yangon, Myanmar');
        AppSetting::setValue('invoice.student_name_format', 'bilingual');
        $student->updateQuietly([
            'name_en' => 'Aye Chan',
            'name_mm' => 'အေးချမ်း',
        ]);

        $response = $this->actingAs($user)->get(route('student-invoices.print', $invoice));

        $response->assertOk();
        $response->assertSee('Official Student Invoice');
        $response->assertSee($invoice->invoice_no);
        $response->assertSee('Print Invoice');
        $response->assertSee('STAR International School');
        $response->assertSee('Aye Chan / အေးချမ်း');
    }

    public function test_authenticated_users_can_generate_a_student_invoice_from_applicable_fee_structures(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $student = Student::factory()->create();
        $schoolWideCategory = FeeCategory::factory()->create(['name' => 'Registration Fee']);
        $groupCategory = FeeCategory::factory()->create(['name' => 'Primary Tuition']);

        $schoolWideFee = FeeStructure::factory()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => null,
            'fee_category_id' => $schoolWideCategory->id,
            'amount' => 30000,
            'billing_cycle' => 'one-time',
            'status' => 'active',
        ]);

        $groupFee = FeeStructure::factory()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => Grade::GROUP_PRIMARY,
            'fee_category_id' => $groupCategory->id,
            'amount' => 80000,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, [$schoolWideFee, $groupFee]);

        $response = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-27',
            'due_date' => '2026-05-05',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
            'billing_month' => '2026-04',
            'billing_year_label' => 'April 2026',
            'notes' => 'Generated for testing',
        ]);

        $invoice = StudentInvoice::query()->first();

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $this->assertNotNull($invoice);
        $this->assertEquals(110000.0, (float) $invoice->total_amount);
        $this->assertEquals('2026-04', $invoice->billing_month);
        $this->assertEquals($student->id, $invoice->student_id);
        $this->assertNotNull($invoice->enrollment_id);
        $this->assertDatabaseCount('student_invoice_items', 2);
    }

    public function test_authenticated_users_can_generate_a_student_invoice_with_fee_items(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $student = Student::factory()->create();
        $category = FeeCategory::factory()->create([
            'name' => 'School Uniform',
            'allow_discount' => false,
        ]);
        $shirt = FeeItem::factory()->create([
            'fee_category_id' => $category->id,
            'name' => 'Uniform Shirt',
            'variant' => 'Size 28',
            'price' => 25000,
            'status' => 'active',
        ]);
        $pants = FeeItem::factory()->create([
            'fee_category_id' => $category->id,
            'name' => 'Uniform Pants',
            'variant' => 'Size 28',
            'price' => 30000,
            'status' => 'active',
        ]);
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, []);

        $response = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-27',
            'due_date' => '2026-05-05',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_ONE_TIME,
            'billing_year_label' => 'Uniform April 2026',
            'fee_item_ids' => [$shirt->id, $pants->id],
            'fee_item_quantities' => [
                $shirt->id => 2,
                $pants->id => 1,
            ],
        ]);

        $invoice = StudentInvoice::query()->first();

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $this->assertEquals(80000.0, (float) $invoice->total_amount);
        $this->assertDatabaseHas('student_invoice_items', [
            'student_invoice_id' => $invoice->id,
            'fee_item_id' => $shirt->id,
            'quantity' => 2,
            'unit_price' => 25000,
            'amount' => 50000,
        ]);
    }

    public function test_active_student_percentage_discount_is_auto_applied_to_discountable_invoice_items(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $student = Student::factory()->create();
        $feeCategory = FeeCategory::factory()->create([
            'name' => 'Tuition Fee',
            'allow_discount' => true,
        ]);
        $feeStructure = FeeStructure::factory()->create([
            'academic_year_id' => $academicYear->id,
            'grade_group' => Grade::GROUP_PRIMARY,
            'grade_id' => null,
            'fee_category_id' => $feeCategory->id,
            'amount' => 100000,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);
        $discountDefinition = DiscountDefinition::factory()->create([
            'name' => 'Sibling Discount',
            'discount_type' => DiscountDefinition::TYPE_PERCENTAGE,
            'value' => 10,
            'status' => 'active',
        ]);
        StudentDiscount::factory()->create([
            'student_id' => $student->id,
            'discount_definition_id' => $discountDefinition->id,
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'notes' => 'Auto scholarship',
        ]);
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, [$feeStructure]);

        $response = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-27',
            'due_date' => '2026-05-05',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
            'billing_month' => '2026-04',
            'billing_year_label' => 'April 2026',
        ]);

        $invoice = StudentInvoice::query()->first();

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $this->assertEquals(90000.0, (float) $invoice->fresh()->total_amount);
        $this->assertDatabaseHas('student_invoice_discounts', [
            'student_invoice_id' => $invoice->id,
            'discount_definition_id' => $discountDefinition->id,
            'amount' => 10000,
        ]);
    }

    public function test_auto_applied_student_discount_skips_non_discountable_material_items(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $student = Student::factory()->create();
        $materialCategory = FeeCategory::factory()->create([
            'name' => 'School Uniform',
            'allow_discount' => false,
        ]);
        $feeItem = FeeItem::factory()->create([
            'fee_category_id' => $materialCategory->id,
            'price' => 25000,
            'status' => 'active',
        ]);
        $discountDefinition = DiscountDefinition::factory()->create([
            'discount_type' => DiscountDefinition::TYPE_PERCENTAGE,
            'value' => 20,
            'status' => 'active',
        ]);
        StudentDiscount::factory()->create([
            'student_id' => $student->id,
            'discount_definition_id' => $discountDefinition->id,
            'start_date' => '2026-04-01',
            'status' => 'active',
        ]);
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, []);

        $response = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-27',
            'due_date' => '2026-05-05',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_ONE_TIME,
            'billing_year_label' => 'Uniform April 2026',
            'fee_item_ids' => [$feeItem->id],
            'fee_item_quantities' => [
                $feeItem->id => 1,
            ],
        ]);

        $invoice = StudentInvoice::query()->first();

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $this->assertEquals(25000.0, (float) $invoice->fresh()->total_amount);
        $this->assertDatabaseCount('student_invoice_discounts', 0);
    }

    public function test_fixed_student_discount_is_distributed_across_discountable_invoice_items(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $student = Student::factory()->create();
        $tuitionCategory = FeeCategory::factory()->create([
            'name' => 'Tuition Fee',
            'allow_discount' => true,
        ]);
        $examCategory = FeeCategory::factory()->create([
            'name' => 'Exam Fee',
            'allow_discount' => true,
        ]);
        $tuitionFee = FeeStructure::factory()->create([
            'academic_year_id' => $academicYear->id,
            'grade_group' => Grade::GROUP_PRIMARY,
            'grade_id' => null,
            'fee_category_id' => $tuitionCategory->id,
            'amount' => 80000,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);
        $examFee = FeeStructure::factory()->create([
            'academic_year_id' => $academicYear->id,
            'grade_group' => Grade::GROUP_PRIMARY,
            'grade_id' => null,
            'fee_category_id' => $examCategory->id,
            'amount' => 40000,
            'billing_cycle' => 'one-time',
            'status' => 'active',
        ]);
        $discountDefinition = DiscountDefinition::factory()->create([
            'discount_type' => DiscountDefinition::TYPE_FIXED,
            'value' => 90000,
            'status' => 'active',
        ]);
        StudentDiscount::factory()->create([
            'student_id' => $student->id,
            'discount_definition_id' => $discountDefinition->id,
            'start_date' => '2026-04-01',
            'status' => 'active',
        ]);
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, [$tuitionFee, $examFee]);

        $response = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-27',
            'due_date' => '2026-05-05',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
            'billing_month' => '2026-04',
            'billing_year_label' => 'April 2026',
        ]);

        $invoice = StudentInvoice::query()->first();

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $this->assertEquals(30000.0, (float) $invoice->fresh()->total_amount);
        $this->assertDatabaseHas('student_invoice_discounts', [
            'student_invoice_id' => $invoice->id,
            'discount_definition_id' => $discountDefinition->id,
            'amount' => 80000,
        ]);
        $this->assertDatabaseHas('student_invoice_discounts', [
            'student_invoice_id' => $invoice->id,
            'discount_definition_id' => $discountDefinition->id,
            'amount' => 10000,
        ]);
    }

    public function test_invoice_requires_a_fee_structure_or_fee_item(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('student-invoices.create'))
            ->post(route('student-invoices.store'), [
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'issue_date' => '2026-04-27',
                'due_date' => '2026-05-05',
                'status' => 'issued',
                'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
                'billing_month' => '2026-04',
                'billing_year_label' => 'April 2026',
                'fee_item_ids' => [],
            ]);

        $response->assertRedirect(route('student-invoices.create'));
        $response->assertSessionHasErrors('academic_year_id');
    }

    public function test_installment_fee_structure_expands_into_multiple_invoice_items(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PEARSON_IGCSE]);
        $student = Student::factory()->create();
        $feeCategory = FeeCategory::factory()->create(['name' => 'IGCSE Foundation Class Fee']);

        $installmentFee = FeeStructure::factory()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => Grade::GROUP_PEARSON_IGCSE,
            'fee_category_id' => $feeCategory->id,
            'amount' => 2700000,
            'billing_cycle' => 'installment',
            'status' => 'active',
        ]);

        $installmentFee->installments()->createMany([
            ['installment_no' => 1, 'amount' => 1350000, 'due_date' => '2026-06-01', 'remarks' => 'First'],
            ['installment_no' => 2, 'amount' => 1350000, 'due_date' => '2026-09-01', 'remarks' => 'Second'],
        ]);
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, [$installmentFee]);

        $response = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-27',
            'due_date' => '2026-05-05',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_INSTALLMENT,
            'billing_year_label' => 'IGCSE Foundation 2026',
        ]);

        $invoice = StudentInvoice::query()->first();

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $this->assertEquals(2700000.0, (float) $invoice->total_amount);
        $this->assertDatabaseCount('student_invoice_items', 2);
        $this->assertDatabaseHas('student_invoice_items', [
            'student_invoice_id' => $invoice->id,
            'installment_no' => 1,
            'amount' => 1350000,
        ]);
    }

    public function test_fee_structure_must_match_student_scope(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $primaryGrade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $secondaryGrade = Grade::factory()->create(['grade_group' => Grade::GROUP_SECONDARY]);
        $student = Student::factory()->create();
        $feeCategory = FeeCategory::factory()->create();
        $secondaryFee = FeeStructure::factory()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => Grade::GROUP_SECONDARY,
            'fee_category_id' => $feeCategory->id,
            'status' => 'active',
        ]);
        $this->assignFeePlanToEnrollment($student, $academicYear, $primaryGrade, []);

        $response = $this->actingAs($user)
            ->from(route('student-invoices.create'))
            ->post(route('student-invoices.store'), [
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'issue_date' => '2026-04-27',
                'due_date' => '2026-05-05',
                'status' => 'issued',
                'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
                'billing_month' => '2026-04',
                'billing_year_label' => 'April 2026',
            ]);

        $response->assertRedirect(route('student-invoices.create'));
        $response->assertSessionHasErrors('academic_year_id');
    }

    public function test_authenticated_users_can_view_student_invoice_preview(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $student = Student::factory()->create();
        $feeCategory = FeeCategory::factory()->create(['name' => 'Primary Tuition']);
        FeeStructure::factory()->create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => null,
            'grade_group' => Grade::GROUP_PRIMARY,
            'fee_category_id' => $feeCategory->id,
            'amount' => 80000,
            'status' => 'active',
        ]);
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, FeeStructure::query()->where('academic_year_id', $academicYear->id)->get()->all());

        $response = $this->actingAs($user)->get(route('student-invoices.preview', $student));

        $response->assertOk();
        $response->assertSee(trim(collect([
            trim((string) $student->preferred_name),
            trim((string) ($student->name_en ?: $student->full_name)),
            trim((string) $student->name_mm),
        ])->filter()->unique()->implode(' / ')));
        $response->assertSee('Primary Tuition');
    }

    public function test_invoice_detail_page_shows_auto_applied_discount_summary_and_amounts(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $invoice = StudentInvoice::query()->create([
            'invoice_no' => 'INV-SHOW-0001',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-28',
            'due_date' => '2026-05-05',
            'status' => 'issued',
            'total_amount' => 90000,
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
            'name' => 'Sibling Discount',
            'discount_type' => DiscountDefinition::TYPE_PERCENTAGE,
            'value' => 10,
            'status' => 'active',
        ]);
        StudentInvoiceDiscount::query()->create([
            'student_invoice_id' => $invoice->id,
            'student_invoice_item_id' => $item->id,
            'discount_definition_id' => $discountDefinition->id,
            'discount_type' => DiscountDefinition::TYPE_PERCENTAGE,
            'value' => 10,
            'amount' => 10000,
            'reason' => $discountDefinition->name,
            'notes' => StudentInvoiceDiscount::AUTO_APPLIED_NOTE_PREFIX,
        ]);
        $invoice->load(['items', 'discounts', 'payments']);
        $invoice->recalculateTotals();

        $response = $this->actingAs($user)->get(route('student-invoices.show', $invoice));

        $response->assertOk();
        $response->assertSee('Auto-Applied Student Discounts');
        $response->assertSee('Sibling Discount');
        $response->assertSee('10,000.00');
    }

    public function test_unpaid_invoice_pages_show_collect_payment_actions(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $invoice = StudentInvoice::query()->create([
            'invoice_no' => 'INV-PAY-0001',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-28',
            'due_date' => '2026-05-05',
            'status' => 'issued',
            'total_amount' => 50000,
        ]);

        $indexResponse = $this->actingAs($user)->get(route('student-invoices.index'));
        $showResponse = $this->actingAs($user)->get(route('student-invoices.show', $invoice));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Make Payment');
        $showResponse->assertOk();
        $showResponse->assertSee('Go to Payment Form');
        $showResponse->assertSee('Collect Payment');
    }

    public function test_draft_invoice_can_be_issued(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $invoice = StudentInvoice::query()->create([
            'invoice_no' => 'INV-DRAFT-0001',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_CUSTOM,
            'billing_year_label' => 'Draft Test',
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->patch(route('student-invoices.update-status', $invoice), [
            'action' => 'issue',
        ]);

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $invoice->refresh();
        $this->assertEquals(\App\Models\StudentInvoice::STATUS_ISSUED, $invoice->status);
        $this->assertNotNull($invoice->issued_at);
    }

    public function test_draft_invoice_can_be_cancelled_without_delete(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $invoice = StudentInvoice::query()->create([
            'invoice_no' => 'INV-CANCEL-0001',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_CUSTOM,
            'billing_year_label' => 'Draft Cancel Test',
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->patch(route('student-invoices.update-status', $invoice), [
            'action' => 'cancel',
        ]);

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $invoice->refresh();
        $this->assertEquals(\App\Models\StudentInvoice::STATUS_CANCELLED, $invoice->status);
        $this->assertDatabaseHas('student_invoices', [
            'id' => $invoice->id,
            'status' => \App\Models\StudentInvoice::STATUS_CANCELLED,
        ]);
    }

    public function test_issued_invoice_without_payments_can_be_voided(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $invoice = StudentInvoice::query()->create([
            'invoice_no' => 'INV-VOID-0001',
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => now()->toDateString(),
            'status' => 'issued',
            'issued_at' => now(),
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_CUSTOM,
            'billing_year_label' => 'Issued Void Test',
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->patch(route('student-invoices.update-status', $invoice), [
            'action' => 'void',
        ]);

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $invoice->refresh();
        $this->assertEquals(\App\Models\StudentInvoice::STATUS_VOID, $invoice->status);
        $this->assertNotNull($invoice->voided_at);
    }

    public function test_duplicate_active_invoice_for_same_enrollment_and_period_is_blocked(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $student = Student::factory()->create();
        $feeCategory = FeeCategory::factory()->create(['name' => 'Primary Tuition']);
        $feeStructure = FeeStructure::factory()->create([
            'academic_year_id' => $academicYear->id,
            'grade_group' => Grade::GROUP_PRIMARY,
            'grade_id' => null,
            'fee_category_id' => $feeCategory->id,
            'amount' => 80000,
            'status' => 'active',
        ]);
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, [$feeStructure]);

        $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-27',
            'due_date' => '2026-05-05',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
            'billing_month' => '2026-04',
            'billing_year_label' => 'April 2026',
        ]);

        $response = $this->actingAs($user)
            ->from(route('student-invoices.create'))
            ->post(route('student-invoices.store'), [
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'issue_date' => '2026-04-28',
                'due_date' => '2026-05-05',
                'status' => 'issued',
                'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
                'billing_month' => '2026-04',
                'billing_year_label' => 'April 2026',
            ]);

        $response->assertRedirect(route('student-invoices.create'));
        $response->assertSessionHasErrors('billing_period_type');
    }

    public function test_invoice_numbers_follow_academic_year_sequence_policy(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create(['name' => '2026-2027']);
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);

        $studentOne = Student::factory()->create();
        $studentTwo = Student::factory()->create();

        $feeCategory = FeeCategory::factory()->create(['name' => 'Primary Tuition']);
        $feeStructure = FeeStructure::factory()->create([
            'academic_year_id' => $academicYear->id,
            'grade_group' => Grade::GROUP_PRIMARY,
            'grade_id' => null,
            'fee_category_id' => $feeCategory->id,
            'amount' => 80000,
            'status' => 'active',
        ]);

        $this->assignFeePlanToEnrollment($studentOne, $academicYear, $grade, [$feeStructure]);
        $this->assignFeePlanToEnrollment($studentTwo, $academicYear, $grade, [$feeStructure]);

        $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $studentOne->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-27',
            'due_date' => '2026-05-05',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
            'billing_month' => '2026-04',
            'billing_year_label' => 'April 2026',
        ]);

        $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $studentTwo->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-27',
            'due_date' => '2026-05-05',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
            'billing_month' => '2026-04',
            'billing_year_label' => 'April 2026',
        ]);

        $invoiceNumbers = StudentInvoice::query()->orderBy('id')->pluck('invoice_no')->all();

        $this->assertSame('INV/2026-2027/00001', $invoiceNumbers[0]);
        $this->assertSame('INV/2026-2027/00002', $invoiceNumbers[1]);
    }

    private function assignFeePlanToEnrollment(Student $student, AcademicYear $academicYear, Grade $grade, array $feeStructures): FeePlan
    {
        $existingEnrollment = $student->enrollments()
            ->where('academic_year_id', $academicYear->id)
            ->where('grade_id', $grade->id)
            ->first();

        $section = $existingEnrollment?->section && (int) $existingEnrollment->section->grade_id === (int) $grade->id
            ? $existingEnrollment->section
            : Section::factory()->create(['grade_id' => $grade->id]);

        $student->enrollments()
            ->where('academic_year_id', '!=', $academicYear->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->update(['status' => Enrollment::STATUS_COMPLETED]);

        $plan = FeePlan::query()->create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Plan ' . fake()->unique()->word(),
            'code' => 'PLAN-' . fake()->unique()->numberBetween(1000, 9999),
            'grade_group' => $grade->grade_group,
            'status' => 'active',
        ]);

        $plan->feeStructures()->attach(collect($feeStructures)->pluck('id')->all());

        Enrollment::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
            ],
            [
                'grade_id' => $grade->id,
                'section_id' => $section->id,
                'fee_plan_id' => $plan->id,
                'enrollment_date' => $student->admission_date ?? now()->toDateString(),
                'status' => Enrollment::STATUS_ACTIVE,
            ],
        );

        return $plan;
    }
}
