<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $slug => $definition) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_system' => true,
                ],
            );
        }
    }

    public static function definitions(): array
    {
        return [
            'dashboard.view' => ['name' => 'View Dashboard', 'description' => 'View the main dashboard.'],
            'profile.manage_own' => ['name' => 'Manage Own Profile', 'description' => 'Manage own account profile.'],
            'students.view' => ['name' => 'View Students', 'description' => 'View student records.'],
            'students.create' => ['name' => 'Create Students', 'description' => 'Create student records.'],
            'students.update' => ['name' => 'Update Students', 'description' => 'Update student records.'],
            'students.delete' => ['name' => 'Delete Students', 'description' => 'Delete student records.'],
            'enrollments.view' => ['name' => 'View Enrollments', 'description' => 'View enrollment records.'],
            'enrollments.create' => ['name' => 'Create Enrollments', 'description' => 'Create enrollment records.'],
            'enrollments.update' => ['name' => 'Update Enrollments', 'description' => 'Update enrollment records.'],
            'enrollments.delete' => ['name' => 'Delete Enrollments', 'description' => 'Delete enrollment records.'],
            'attendance.view' => ['name' => 'View Attendance', 'description' => 'View attendance pages and records.'],
            'attendance.create' => ['name' => 'Create Attendance', 'description' => 'Record attendance.'],
            'attendance.update' => ['name' => 'Update Attendance', 'description' => 'Update attendance records.'],
            'marks.view' => ['name' => 'View Marks', 'description' => 'View marks records.'],
            'marks.create' => ['name' => 'Create Marks', 'description' => 'Create marks records.'],
            'marks.update' => ['name' => 'Update Marks', 'description' => 'Update marks records.'],
            'marks.delete' => ['name' => 'Delete Marks', 'description' => 'Delete marks records.'],
            'report_cards.view' => ['name' => 'View Report Cards', 'description' => 'View report cards.'],
            'report_cards.print' => ['name' => 'Print Report Cards', 'description' => 'Print report cards.'],
            'student_daily_reports.view' => ['name' => 'View Student Daily Reports', 'description' => 'View student daily reports.'],
            'student_daily_reports.create' => ['name' => 'Create Student Daily Reports', 'description' => 'Create student daily reports.'],
            'student_daily_reports.update' => ['name' => 'Update Student Daily Reports', 'description' => 'Update student daily reports.'],
            'student_daily_reports.delete' => ['name' => 'Delete Student Daily Reports', 'description' => 'Delete student daily reports.'],
            'staff.view' => ['name' => 'View Staff', 'description' => 'View staff records.'],
            'staff.create' => ['name' => 'Create Staff', 'description' => 'Create staff records.'],
            'staff.update' => ['name' => 'Update Staff', 'description' => 'Update staff records.'],
            'staff.delete' => ['name' => 'Delete Staff', 'description' => 'Delete staff records.'],
            'academic_years.manage' => ['name' => 'Manage Academic Years', 'description' => 'Manage academic year setup.'],
            'grades.manage' => ['name' => 'Manage Grades', 'description' => 'Manage grades.'],
            'sections.manage' => ['name' => 'Manage Sections', 'description' => 'Manage sections.'],
            'subjects.manage' => ['name' => 'Manage Subjects', 'description' => 'Manage subjects.'],
            'exams.manage' => ['name' => 'Manage Exams', 'description' => 'Manage exams.'],
            'rooms.manage' => ['name' => 'Manage Rooms', 'description' => 'Manage rooms.'],
            'fee_categories.manage' => ['name' => 'Manage Fee Categories', 'description' => 'Manage fee categories.'],
            'fee_items.manage' => ['name' => 'Manage Fee Items', 'description' => 'Manage fee items.'],
            'fee_structures.manage' => ['name' => 'Manage Fee Structures', 'description' => 'Manage fee structures.'],
            'fee_plans.manage' => ['name' => 'Manage Fee Plans', 'description' => 'Manage fee plans.'],
            'discount_definitions.manage' => ['name' => 'Manage Discount Definitions', 'description' => 'Manage discount definitions.'],
            'student_discounts.manage' => ['name' => 'Manage Student Discounts', 'description' => 'Manage student discounts.'],
            'student_invoices.view' => ['name' => 'View Student Invoices', 'description' => 'View student invoices.'],
            'student_invoices.create' => ['name' => 'Create Student Invoices', 'description' => 'Create student invoices.'],
            'student_invoices.issue' => ['name' => 'Issue Student Invoices', 'description' => 'Issue student invoices.'],
            'student_invoices.void' => ['name' => 'Void Student Invoices', 'description' => 'Void student invoices.'],
            'student_invoices.print' => ['name' => 'Print Student Invoices', 'description' => 'Print student invoices.'],
            'student_payments.view' => ['name' => 'View Student Payments', 'description' => 'View student payments.'],
            'student_payments.collect' => ['name' => 'Collect Student Payments', 'description' => 'Collect student payments.'],
            'student_payments.delete' => ['name' => 'Delete Student Payments', 'description' => 'Delete student payments.'],
            'external_exam_sessions.manage' => ['name' => 'Manage External Exam Sessions', 'description' => 'Manage external exam sessions.'],
            'external_exam_registrations.manage' => ['name' => 'Manage External Exam Registrations', 'description' => 'Manage external exam registrations.'],
            'external_exam_payments.view' => ['name' => 'View External Exam Payments', 'description' => 'View external exam payments.'],
            'external_exam_payments.collect' => ['name' => 'Collect External Exam Payments', 'description' => 'Collect external exam payments.'],
            'external_exam_payments.delete' => ['name' => 'Delete External Exam Payments', 'description' => 'Delete external exam payments.'],
            'wallets.view' => ['name' => 'View Wallets', 'description' => 'View wallet balances and ledger history.'],
            'wallets.topup' => ['name' => 'Top Up Wallets', 'description' => 'Top up prepaid wallet balances.'],
            'wallets.adjust' => ['name' => 'Adjust Wallets', 'description' => 'Adjust or reverse wallet balances.'],
            'wallet_transactions.view' => ['name' => 'View Wallet Transactions', 'description' => 'View wallet transaction receipts and ledger details.'],
            'pos_products.manage' => ['name' => 'Manage POS Products', 'description' => 'Manage POS products and pricing.'],
            'pos_sales.create' => ['name' => 'Create POS Sales', 'description' => 'Create POS checkout sales.'],
            'pos_sales.view' => ['name' => 'View POS Sales', 'description' => 'View POS sale records.'],
            'pos_sales.reverse' => ['name' => 'Reverse POS Sales', 'description' => 'Reverse POS sale records.'],
            'pos_reports.view' => ['name' => 'View POS Reports', 'description' => 'View POS reporting and summaries.'],
            'reports.financial.view' => ['name' => 'View Financial Reports', 'description' => 'View financial reports.'],
            'reports.receivables.view' => ['name' => 'View Receivables Reports', 'description' => 'View receivables reports.'],
            'reports.academic.view' => ['name' => 'View Academic Reports', 'description' => 'View academic reports.'],
            'audit_logs.finance.view' => ['name' => 'View Finance Audit Logs', 'description' => 'View finance audit trail records.'],
            'audit_logs.academic.view' => ['name' => 'View Academic Audit Logs', 'description' => 'View academic audit trail records.'],
            'audit_logs.settings.view' => ['name' => 'View Settings Audit Logs', 'description' => 'View settings audit trail records.'],
            'id_cards.students.print' => ['name' => 'Print Student ID Cards', 'description' => 'Print student ID cards.'],
            'id_cards.staff.print' => ['name' => 'Print Staff ID Cards', 'description' => 'Print staff ID cards.'],
            'admin_settings.manage' => ['name' => 'Manage Admin Settings', 'description' => 'Manage admin settings.'],
        ];
    }
}
