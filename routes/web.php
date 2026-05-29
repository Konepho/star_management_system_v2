<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscountDefinitionController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExternalExamPaymentController;
use App\Http\Controllers\ExternalExamRegistrationController;
use App\Http\Controllers\ExternalExamSessionController;
use App\Http\Controllers\FeeCategoryController;
use App\Http\Controllers\FeeItemController;
use App\Http\Controllers\FeePlanController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\MarkController;
use App\Http\Controllers\PosCashierController;
use App\Http\Controllers\PosProductCategoryController;
use App\Http\Controllers\PosProductController;
use App\Http\Controllers\PosReportController;
use App\Http\Controllers\PosSaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceivablesReportController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDailyReportController;
use App\Http\Controllers\StudentDiscountController;
use App\Http\Controllers\StudentIdCardController;
use App\Http\Controllers\StudentInvoiceController;
use App\Http\Controllers\StudentInvoiceDiscountController;
use App\Http\Controllers\StudentPaymentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WalletAdjustmentController;
use App\Http\Controllers\WalletTopupController;
use App\Http\Controllers\WalletTransactionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'permission:dashboard.view'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('academic-years', AcademicYearController::class)
        ->except('show')
        ->middleware('permission:academic_years.manage');

    Route::get('admin-settings', [AdminSettingsController::class, 'edit'])
        ->middleware('permission:admin_settings.manage')
        ->name('admin-settings.edit');
    Route::put('admin-settings', [AdminSettingsController::class, 'update'])
        ->middleware('permission:admin_settings.manage')
        ->name('admin-settings.update');
    Route::get('audit-logs', [AuditLogController::class, 'index'])
        ->middleware('permission:audit_logs.finance.view,audit_logs.academic.view,audit_logs.settings.view')
        ->name('audit-logs.index');

    Route::resource('fee-categories', FeeCategoryController::class)
        ->except('show')
        ->middleware('permission:fee_categories.manage');
    Route::resource('discount-definitions', DiscountDefinitionController::class)
        ->except('show')
        ->middleware('permission:discount_definitions.manage');
    Route::resource('fee-items', FeeItemController::class)
        ->except('show')
        ->middleware('permission:fee_items.manage');
    Route::resource('fee-plans', FeePlanController::class)
        ->except('show')
        ->middleware('permission:fee_plans.manage');
    Route::resource('fee-structures', FeeStructureController::class)
        ->except('show')
        ->middleware('permission:fee_structures.manage');
    Route::resource('student-discounts', StudentDiscountController::class)
        ->except('show')
        ->middleware('permission:student_discounts.manage');
    Route::resource('student-daily-reports', StudentDailyReportController::class)
        ->except('show')
        ->middlewareFor('index', 'permission:student_daily_reports.view')
        ->middlewareFor(['create', 'store'], 'permission:student_daily_reports.create')
        ->middlewareFor(['edit', 'update'], 'permission:student_daily_reports.update')
        ->middlewareFor('destroy', 'permission:student_daily_reports.delete');

    Route::post('student-id-cards/print-selected', [StudentIdCardController::class, 'bulkPrint'])
        ->middleware('permission:id_cards.students.print,id_cards.staff.print')
        ->name('student-id-cards.bulk-print');
    Route::get('student-id-cards/staff/{staff}/print', [StudentIdCardController::class, 'printStaff'])
        ->middleware('permission:id_cards.staff.print')
        ->name('student-id-cards.print-staff');
    Route::get('student-id-cards/{student}/print', [StudentIdCardController::class, 'show'])
        ->middleware('permission:id_cards.students.print')
        ->name('student-id-cards.print');
    Route::get('student-id-cards', [StudentIdCardController::class, 'index'])
        ->middleware('permission:id_cards.students.print,id_cards.staff.print')
        ->name('student-id-cards.index');

    Route::get('students/{student}/invoice-preview', [StudentInvoiceController::class, 'preview'])
        ->middleware('permission:student_invoices.create')
        ->name('student-invoices.preview');
    Route::get('student-invoices/{studentInvoice}/print', [StudentInvoiceController::class, 'print'])
        ->middleware('permission:student_invoices.print')
        ->name('student-invoices.print');
    Route::patch('student-invoices/{studentInvoice}/status', [StudentInvoiceController::class, 'updateStatus'])
        ->middleware('permission:student_invoices.issue,student_invoices.void')
        ->name('student-invoices.update-status');
    Route::resource('student-invoices', StudentInvoiceController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->middlewareFor(['index', 'show'], 'permission:student_invoices.view')
        ->middlewareFor(['create', 'store'], 'permission:student_invoices.create');
    Route::resource('student-invoice-discounts', StudentInvoiceDiscountController::class)
        ->only(['store', 'destroy'])
        ->middleware('permission:student_discounts.manage');
    Route::resource('student-payments', StudentPaymentController::class)
        ->only(['index', 'show', 'store', 'destroy'])
        ->middlewareFor(['index', 'show'], 'permission:student_payments.view')
        ->middlewareFor('store', 'permission:student_payments.collect')
        ->middlewareFor('destroy', 'permission:student_payments.delete');
    Route::get('wallets', [WalletController::class, 'index'])
        ->middleware('permission:wallets.view')
        ->name('wallets.index');
    Route::get('wallets/{wallet}', [WalletController::class, 'show'])
        ->middleware('permission:wallets.view')
        ->name('wallets.show');
    Route::get('wallet-topups/create', [WalletTopupController::class, 'create'])
        ->middleware('permission:wallets.topup')
        ->name('wallet-topups.create');
    Route::post('wallet-topups', [WalletTopupController::class, 'store'])
        ->middleware('permission:wallets.topup')
        ->name('wallet-topups.store');
    Route::post('wallet-adjustments', [WalletAdjustmentController::class, 'store'])
        ->middleware('permission:wallets.adjust')
        ->name('wallet-adjustments.store');
    Route::delete('wallet-transactions/{walletTransaction}', [WalletTransactionController::class, 'destroy'])
        ->middleware('permission:wallets.adjust')
        ->name('wallet-transactions.destroy');
    Route::get('pos-cashier', PosCashierController::class)
        ->middleware('permission:wallets.topup,pos_sales.create')
        ->name('pos-cashier.index');
    Route::resource('pos-products', PosProductController::class)
        ->except('show')
        ->middleware('permission:pos_products.manage');
    Route::resource('pos-product-categories', PosProductCategoryController::class)
        ->except('show')
        ->middleware('permission:pos_products.manage');
    Route::resource('pos-sales', PosSaleController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy'])
        ->middlewareFor(['index', 'show'], 'permission:pos_sales.view')
        ->middlewareFor(['create', 'store'], 'permission:pos_sales.create')
        ->middlewareFor('destroy', 'permission:pos_sales.reverse');
    Route::get('reports/pos', PosReportController::class)
        ->middleware('permission:pos_reports.view')
        ->name('reports.pos');

    Route::get('reports/financial', FinancialReportController::class)
        ->middleware('permission:reports.financial.view')
        ->name('reports.financial');
    Route::get('reports/receivables', ReceivablesReportController::class)
        ->middleware('permission:reports.receivables.view')
        ->name('reports.receivables');

    Route::resource('grades', GradeController::class)
        ->except('show')
        ->middleware('permission:grades.manage');
    Route::resource('rooms', RoomController::class)
        ->except('show')
        ->middleware('permission:rooms.manage');
    Route::resource('sections', SectionController::class)
        ->except('show')
        ->middleware('permission:sections.manage');
    Route::resource('enrollments', EnrollmentController::class)
        ->except('show')
        ->middlewareFor('index', 'permission:enrollments.view')
        ->middlewareFor(['create', 'store'], 'permission:enrollments.create')
        ->middlewareFor(['edit', 'update'], 'permission:enrollments.update')
        ->middlewareFor('destroy', 'permission:enrollments.delete');
    Route::resource('students', StudentController::class)
        ->except('show')
        ->middlewareFor('index', 'permission:students.view')
        ->middlewareFor(['create', 'store'], 'permission:students.create')
        ->middlewareFor(['edit', 'update'], 'permission:students.update')
        ->middlewareFor('destroy', 'permission:students.delete');
    Route::resource('staff', StaffController::class)
        ->except('show')
        ->middlewareFor('index', 'permission:staff.view')
        ->middlewareFor(['create', 'store'], 'permission:staff.create')
        ->middlewareFor(['edit', 'update'], 'permission:staff.update')
        ->middlewareFor('destroy', 'permission:staff.delete');
    Route::resource('subjects', SubjectController::class)
        ->except('show')
        ->middleware('permission:subjects.manage');
    Route::resource('exams', ExamController::class)
        ->except('show')
        ->middleware('permission:exams.manage');
    Route::resource('external-exam-sessions', ExternalExamSessionController::class)
        ->except('show')
        ->middleware('permission:external_exam_sessions.manage');
    Route::resource('external-exam-registrations', ExternalExamRegistrationController::class)
        ->middleware('permission:external_exam_registrations.manage');
    Route::resource('external-exam-payments', ExternalExamPaymentController::class)
        ->only(['index', 'show', 'store', 'destroy'])
        ->middlewareFor(['index', 'show'], 'permission:external_exam_payments.view')
        ->middlewareFor('store', 'permission:external_exam_payments.collect')
        ->middlewareFor('destroy', 'permission:external_exam_payments.delete');
    Route::resource('marks', MarkController::class)
        ->except('show')
        ->middlewareFor('index', 'permission:marks.view')
        ->middlewareFor(['create', 'store'], 'permission:marks.create')
        ->middlewareFor(['edit', 'update'], 'permission:marks.update')
        ->middlewareFor('destroy', 'permission:marks.delete');
    Route::resource('attendances', AttendanceController::class)
        ->only(['index', 'create', 'store'])
        ->middlewareFor('index', 'permission:attendance.view')
        ->middlewareFor(['create', 'store'], 'permission:attendance.create,attendance.update');
    Route::get('report-cards', [ReportCardController::class, 'index'])
        ->middleware('permission:report_cards.view')
        ->name('report-cards.index');
    Route::get('report-cards/{exam}/{student}', [ReportCardController::class, 'show'])
        ->middleware('permission:report_cards.view')
        ->name('report-cards.show');
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->middleware('permission:profile.manage_own')
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->middleware('permission:profile.manage_own')
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->middleware('permission:profile.manage_own')
        ->name('profile.destroy');
});

require __DIR__.'/auth.php';
