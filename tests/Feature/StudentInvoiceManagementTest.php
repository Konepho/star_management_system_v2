<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
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
use App\Models\StudentExtraClassRegistration;
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
        $response->assertSee('1. Student and Period');
        $response->assertSee('Estimated Invoice Total');
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
            'notes' => 'Visible to parent',
            'internal_notes' => 'Finance only',
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
        $response->assertSee('Visible to parent');
        $response->assertDontSee('Finance only');
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
        $this->assertEquals(StudentInvoice::STREAM_ACADEMIC, $invoice->invoice_stream);
        $this->assertDatabaseCount('student_invoice_items', 2);
    }

    public function test_academic_fee_rows_can_be_excluded_from_the_current_invoice_only(): void
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

        $excludedKey = 'fee-structure:' . $schoolWideFee->id . ':installment:base';

        $response = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-04-27',
            'due_date' => '2026-05-05',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
            'billing_month' => '2026-04',
            'billing_year_label' => 'April 2026',
            'excluded_academic_charge_keys' => [$excludedKey],
            'excluded_academic_charge_reasons' => [
                $excludedKey => 'Special approval',
            ],
        ]);

        $invoice = StudentInvoice::query()->first();

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $this->assertNotNull($invoice);
        $this->assertEquals(80000.0, (float) $invoice->total_amount);
        $this->assertDatabaseCount('student_invoice_items', 1);
        $this->assertDatabaseHas('student_invoice_items', [
            'student_invoice_id' => $invoice->id,
            'fee_structure_id' => $groupFee->id,
            'amount' => 80000,
        ]);
        $this->assertSame(null, $invoice->notes);
        $this->assertStringContainsString('Academic fee exclusions: Registration Fee (Special approval)', (string) $invoice->internal_notes);

        $auditLog = AuditLog::query()->latest('id')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('student_invoices', $auditLog->module);
        $this->assertEquals('Special approval', $auditLog->meta['excluded_academic_charges'][0]['reason'] ?? null);
        $this->assertEquals('Registration Fee', $auditLog->meta['excluded_academic_charges'][0]['description'] ?? null);
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
        $this->assertEquals(StudentInvoice::STREAM_OTHER, $invoice->invoice_stream);
        $this->assertDatabaseHas('student_invoice_items', [
            'student_invoice_id' => $invoice->id,
            'fee_item_id' => $shirt->id,
            'quantity' => 2,
            'unit_price' => 25000,
            'amount' => 50000,
        ]);
    }

    public function test_authenticated_users_can_generate_a_student_invoice_with_extra_class_charges(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $student = Student::factory()->create();
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, []);

        $extraCategory = FeeCategory::factory()->create(['name' => 'Extra Class Fee']);
        $catalog = \App\Models\ExtraClassCatalog::query()->create([
            'name' => 'YLE Preparation',
            'code' => 'YLE-PREP',
            'status' => 'active',
        ]);
        $offering = \App\Models\ExtraClassOffering::query()->create([
            'extra_class_catalog_id' => $catalog->id,
            'academic_year_id' => $academicYear->id,
            'fee_category_id' => $extraCategory->id,
            'fee_amount' => 95000,
            'status' => 'active',
        ]);
        $registration = StudentExtraClassRegistration::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'extra_class_offering_id' => $offering->id,
            'registration_date' => '2026-06-10',
            'status' => StudentExtraClassRegistration::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-20',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_ONE_TIME,
            'billing_year_label' => 'YLE June 2026',
            'extra_registration_ids' => [$registration->id],
        ]);

        $invoice = StudentInvoice::query()->first();

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $this->assertEquals(95000.0, (float) $invoice->total_amount);
        $this->assertEquals(StudentInvoice::STREAM_EXTRA_CLASS, $invoice->invoice_stream);
        $this->assertDatabaseHas('student_invoice_items', [
            'student_invoice_id' => $invoice->id,
            'student_extra_class_registration_id' => $registration->id,
            'extra_class_offering_id' => $offering->id,
            'amount' => 95000,
        ]);
    }

    public function test_student_with_only_extra_class_registration_can_be_invoiced_without_main_enrollment(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $student = Student::factory()->create(['status' => Student::STATUS_ACTIVE]);

        $extraCategory = FeeCategory::factory()->create(['name' => 'Extra Class Fee']);
        $catalog = \App\Models\ExtraClassCatalog::query()->create([
            'name' => 'Chinese Club',
            'code' => 'CHINESE-CLUB',
            'status' => 'active',
        ]);
        $offering = \App\Models\ExtraClassOffering::query()->create([
            'extra_class_catalog_id' => $catalog->id,
            'academic_year_id' => $academicYear->id,
            'fee_category_id' => $extraCategory->id,
            'fee_amount' => 55000,
            'status' => 'active',
        ]);
        $registration = StudentExtraClassRegistration::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'extra_class_offering_id' => $offering->id,
            'registration_date' => '2026-06-10',
            'status' => StudentExtraClassRegistration::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-20',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_ONE_TIME,
            'billing_year_label' => 'Extra Class Only June 2026',
            'extra_registration_ids' => [$registration->id],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('student_invoices', 1);
        $invoice = StudentInvoice::query()->first();
        $this->assertNotNull($invoice);
        $this->assertNull($invoice->enrollment_id);
        $this->assertNull($invoice->fee_plan_id);
        $this->assertEquals(55000.0, (float) $invoice->total_amount);
        $this->assertEquals(StudentInvoice::STREAM_EXTRA_CLASS, $invoice->invoice_stream);
        $this->assertDatabaseHas('student_invoice_items', [
            'student_invoice_id' => $invoice->id,
            'student_extra_class_registration_id' => $registration->id,
            'extra_class_offering_id' => $offering->id,
            'amount' => 55000,
        ]);
    }

    public function test_invoice_summary_labels_extra_class_only_student_clearly(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $student = Student::factory()->create(['status' => Student::STATUS_ACTIVE]);

        $extraCategory = FeeCategory::factory()->create(['name' => 'Extra Class Fee']);
        $catalog = \App\Models\ExtraClassCatalog::query()->create([
            'name' => 'Robotics Club',
            'code' => 'ROBOTICS',
            'status' => 'active',
        ]);
        $offering = \App\Models\ExtraClassOffering::query()->create([
            'extra_class_catalog_id' => $catalog->id,
            'academic_year_id' => $academicYear->id,
            'fee_category_id' => $extraCategory->id,
            'fee_amount' => 65000,
            'status' => 'active',
        ]);
        StudentExtraClassRegistration::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'extra_class_offering_id' => $offering->id,
            'registration_date' => '2026-06-10',
            'status' => StudentExtraClassRegistration::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->getJson(route('student-invoices.summary', [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.student_type_label', 'Extra Class Only Student');
        $response->assertJsonPath('data.grade_label', 'Extra Class Only Student');
        $response->assertJsonPath('data.section_label', 'Not assigned for extra class only');
        $response->assertJsonPath('data.fee_plan_label', 'Not required for extra class only');
    }

    public function test_already_billed_extra_class_registration_cannot_be_billed_twice(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $student = Student::factory()->create();
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, []);

        $catalog = \App\Models\ExtraClassCatalog::query()->create([
            'name' => 'Abacus',
            'code' => 'ABACUS',
            'status' => 'active',
        ]);
        $offering = \App\Models\ExtraClassOffering::query()->create([
            'extra_class_catalog_id' => $catalog->id,
            'academic_year_id' => $academicYear->id,
            'fee_amount' => 40000,
            'status' => 'active',
        ]);
        $registration = StudentExtraClassRegistration::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'extra_class_offering_id' => $offering->id,
            'registration_date' => '2026-06-10',
            'status' => StudentExtraClassRegistration::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-20',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
            'billing_month' => '2026-06',
            'billing_year_label' => 'June 2026',
            'extra_registration_ids' => [$registration->id],
        ]);

        $response = $this->actingAs($user)
            ->from(route('student-invoices.create'))
            ->post(route('student-invoices.store'), [
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'issue_date' => '2026-06-16',
                'due_date' => '2026-06-21',
                'status' => 'issued',
                'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
                'billing_month' => '2026-06',
                'billing_year_label' => 'June 2026',
                'extra_registration_ids' => [$registration->id],
            ]);

        $response->assertRedirect(route('student-invoices.create'));
        $response->assertSessionHasErrors('extra_registration_ids');
    }

    public function test_extra_class_registration_can_be_billed_again_in_another_month(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $student = Student::factory()->create();
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, []);

        $catalog = \App\Models\ExtraClassCatalog::query()->create([
            'name' => 'Abacus',
            'code' => 'ABACUS',
            'status' => 'active',
        ]);
        $offering = \App\Models\ExtraClassOffering::query()->create([
            'extra_class_catalog_id' => $catalog->id,
            'academic_year_id' => $academicYear->id,
            'fee_amount' => 40000,
            'status' => 'active',
        ]);
        $registration = StudentExtraClassRegistration::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'extra_class_offering_id' => $offering->id,
            'registration_date' => '2026-06-10',
            'status' => StudentExtraClassRegistration::STATUS_ACTIVE,
        ]);

        $firstResponse = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-20',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
            'billing_month' => '2026-06',
            'billing_year_label' => 'June 2026',
            'extra_registration_ids' => [$registration->id],
        ]);

        $firstInvoice = StudentInvoice::query()->first();
        $firstResponse->assertRedirect(route('student-invoices.show', $firstInvoice));

        $secondResponse = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-07-15',
            'due_date' => '2026-07-20',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
            'billing_month' => '2026-07',
            'billing_year_label' => 'July 2026',
            'extra_registration_ids' => [$registration->id],
        ]);

        $secondInvoice = StudentInvoice::query()->latest('id')->first();
        $secondResponse->assertRedirect(route('student-invoices.show', $secondInvoice));
        $this->assertDatabaseCount('student_invoices', 2);
        $this->assertEquals('2026-07', $secondInvoice->billing_month);
        $this->assertDatabaseHas('student_invoice_items', [
            'student_invoice_id' => $secondInvoice->id,
            'student_extra_class_registration_id' => $registration->id,
            'amount' => 40000,
        ]);
    }

    public function test_academic_and_extra_class_invoices_can_exist_in_the_same_month_for_the_same_student(): void
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
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, [$feeStructure]);
        $excludedAcademicKey = 'fee-structure:' . $feeStructure->id . ':installment:base';

        $extraCategory = FeeCategory::factory()->create(['name' => 'Extra Class Fee']);
        $catalog = \App\Models\ExtraClassCatalog::query()->create([
            'name' => 'Abacus',
            'code' => 'ABACUS',
            'status' => 'active',
        ]);
        $offering = \App\Models\ExtraClassOffering::query()->create([
            'extra_class_catalog_id' => $catalog->id,
            'academic_year_id' => $academicYear->id,
            'fee_category_id' => $extraCategory->id,
            'fee_amount' => 40000,
            'status' => 'active',
        ]);
        $registration = StudentExtraClassRegistration::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'extra_class_offering_id' => $offering->id,
            'registration_date' => '2026-06-10',
            'status' => StudentExtraClassRegistration::STATUS_ACTIVE,
        ]);

        $academicResponse = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-20',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
            'billing_month' => '2026-06',
            'billing_year_label' => 'June 2026',
        ]);

        $academicInvoice = StudentInvoice::query()->first();
        $academicResponse->assertRedirect(route('student-invoices.show', $academicInvoice));

        $extraResponse = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-16',
            'due_date' => '2026-06-21',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_MONTHLY,
            'billing_month' => '2026-06',
            'billing_year_label' => 'June 2026',
            'excluded_academic_charge_keys' => [$excludedAcademicKey],
            'excluded_academic_charge_reasons' => [
                $excludedAcademicKey => 'Separate extra class invoice',
            ],
            'extra_registration_ids' => [$registration->id],
        ]);

        $extraInvoice = StudentInvoice::query()->latest('id')->first();
        $extraResponse->assertRedirect(route('student-invoices.show', $extraInvoice));
        $this->assertDatabaseCount('student_invoices', 2);
        $this->assertEquals(StudentInvoice::STREAM_ACADEMIC, $academicInvoice->invoice_stream);
        $this->assertEquals(StudentInvoice::STREAM_EXTRA_CLASS, $extraInvoice->invoice_stream);
        $this->assertDatabaseHas('student_invoice_items', [
            'student_invoice_id' => $academicInvoice->id,
            'fee_structure_id' => $feeStructure->id,
            'amount' => 80000,
        ]);
        $this->assertDatabaseHas('student_invoice_items', [
            'student_invoice_id' => $extraInvoice->id,
            'student_extra_class_registration_id' => $registration->id,
            'amount' => 40000,
        ]);
    }

    public function test_invoice_stream_is_detected_as_mixed_when_academic_and_extra_class_and_other_items_are_combined(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $student = Student::factory()->create();
        $tuitionCategory = FeeCategory::factory()->create(['name' => 'Primary Tuition']);
        $feeStructure = FeeStructure::factory()->create([
            'academic_year_id' => $academicYear->id,
            'grade_group' => Grade::GROUP_PRIMARY,
            'grade_id' => null,
            'fee_category_id' => $tuitionCategory->id,
            'amount' => 80000,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, [$feeStructure]);

        $uniformCategory = FeeCategory::factory()->create([
            'name' => 'Uniform',
            'allow_discount' => false,
        ]);
        $feeItem = FeeItem::factory()->create([
            'fee_category_id' => $uniformCategory->id,
            'name' => 'Sports Shirt',
            'price' => 15000,
            'status' => 'active',
        ]);

        $extraCategory = FeeCategory::factory()->create(['name' => 'Extra Class Fee']);
        $catalog = \App\Models\ExtraClassCatalog::query()->create([
            'name' => 'Robotics',
            'code' => 'ROBOTICS',
            'status' => 'active',
        ]);
        $offering = \App\Models\ExtraClassOffering::query()->create([
            'extra_class_catalog_id' => $catalog->id,
            'academic_year_id' => $academicYear->id,
            'fee_category_id' => $extraCategory->id,
            'fee_amount' => 45000,
            'status' => 'active',
        ]);
        $registration = StudentExtraClassRegistration::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'extra_class_offering_id' => $offering->id,
            'registration_date' => '2026-06-10',
            'status' => StudentExtraClassRegistration::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-20',
            'status' => 'issued',
            'billing_period_type' => StudentInvoice::PERIOD_MONTHLY,
            'billing_month' => '2026-06',
            'billing_year_label' => 'June 2026',
            'fee_item_ids' => [$feeItem->id],
            'extra_registration_ids' => [$registration->id],
        ]);

        $invoice = StudentInvoice::query()->first();

        $response->assertRedirect(route('student-invoices.show', $invoice));
        $this->assertNotNull($invoice);
        $this->assertEquals(StudentInvoice::STREAM_MIXED, $invoice->invoice_stream);
    }

    public function test_invoice_create_page_shows_when_extra_class_registrations_were_already_billed(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $student = Student::factory()->create();
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, []);

        $catalog = \App\Models\ExtraClassCatalog::query()->create([
            'name' => 'Abacus',
            'code' => 'ABACUS',
            'status' => 'active',
        ]);
        $offering = \App\Models\ExtraClassOffering::query()->create([
            'extra_class_catalog_id' => $catalog->id,
            'academic_year_id' => $academicYear->id,
            'fee_amount' => 40000,
            'status' => 'active',
        ]);
        $registration = StudentExtraClassRegistration::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'extra_class_offering_id' => $offering->id,
            'registration_date' => '2026-06-10',
            'status' => StudentExtraClassRegistration::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-20',
            'status' => 'issued',
            'billing_period_type' => \App\Models\StudentInvoice::PERIOD_ONE_TIME,
            'billing_year_label' => 'Abacus June 2026',
            'extra_registration_ids' => [$registration->id],
        ]);

        $response = $this->actingAs($user)->get(route('student-invoices.create'));

        $response->assertOk();
        $response->assertSee('already billed');
        $response->assertSee('All extra class registrations for this student and academic year were already billed for the selected billing period');
    }

    public function test_custom_fee_item_invoices_can_be_created_multiple_times_in_the_same_label(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['grade_group' => Grade::GROUP_PRIMARY]);
        $student = Student::factory()->create();
        $category = FeeCategory::factory()->create([
            'name' => 'Materials',
            'allow_discount' => false,
        ]);
        $feeItem = FeeItem::factory()->create([
            'fee_category_id' => $category->id,
            'name' => 'Workbook',
            'price' => 12000,
            'status' => 'active',
        ]);
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, []);

        $firstResponse = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-20',
            'status' => 'issued',
            'billing_period_type' => StudentInvoice::PERIOD_CUSTOM,
            'billing_year_label' => 'June Material Charges',
            'fee_item_ids' => [$feeItem->id],
        ]);

        $firstInvoice = StudentInvoice::query()->first();
        $firstResponse->assertRedirect(route('student-invoices.show', $firstInvoice));

        $secondResponse = $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-18',
            'due_date' => '2026-06-23',
            'status' => 'issued',
            'billing_period_type' => StudentInvoice::PERIOD_CUSTOM,
            'billing_year_label' => 'June Material Charges',
            'fee_item_ids' => [$feeItem->id],
        ]);

        $secondInvoice = StudentInvoice::query()->latest('id')->first();
        $secondResponse->assertRedirect(route('student-invoices.show', $secondInvoice));
        $secondResponse->assertSessionHasNoErrors();
        $this->assertDatabaseCount('student_invoices', 2);
        $this->assertEquals(StudentInvoice::STREAM_OTHER, $firstInvoice->invoice_stream);
        $this->assertEquals(StudentInvoice::STREAM_OTHER, $secondInvoice->invoice_stream);
    }

    public function test_monthly_academic_invoice_duplicate_is_still_blocked_even_when_fee_items_are_flexible(): void
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
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);
        $this->assignFeePlanToEnrollment($student, $academicYear, $grade, [$feeStructure]);

        $this->actingAs($user)->post(route('student-invoices.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-20',
            'status' => 'issued',
            'billing_period_type' => StudentInvoice::PERIOD_MONTHLY,
            'billing_month' => '2026-06',
            'billing_year_label' => 'June 2026',
        ]);

        $response = $this->actingAs($user)
            ->from(route('student-invoices.create'))
            ->post(route('student-invoices.store'), [
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'issue_date' => '2026-06-18',
                'due_date' => '2026-06-23',
                'status' => 'issued',
                'billing_period_type' => StudentInvoice::PERIOD_MONTHLY,
                'billing_month' => '2026-06',
                'billing_year_label' => 'June 2026',
            ]);

        $response->assertRedirect(route('student-invoices.create'));
        $response->assertSessionHasErrors('billing_period_type');
    }

    public function test_student_without_enrollment_and_without_extra_class_registration_cannot_be_invoiced(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('student-invoices.create'))
            ->post(route('student-invoices.store'), [
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'issue_date' => '2026-06-15',
                'due_date' => '2026-06-20',
                'status' => 'issued',
                'billing_period_type' => \App\Models\StudentInvoice::PERIOD_CUSTOM,
                'billing_year_label' => 'Invalid Extra Only',
            ]);

        $response->assertRedirect(route('student-invoices.create'));
        $response->assertSessionHasErrors('academic_year_id');
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
