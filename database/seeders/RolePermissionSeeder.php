<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::query()->get()->keyBy('slug');
        $permissions = Permission::query()->get()->keyBy('slug');

        foreach ($this->matrix() as $roleSlug => $permissionSlugs) {
            $role = $roles->get($roleSlug);

            if (! $role) {
                continue;
            }

            $permissionIds = collect($permissionSlugs)
                ->map(fn (string $slug) => $permissions->get($slug)?->id)
                ->filter()
                ->values()
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }

    public static function matrix(): array
    {
        $allPermissions = array_keys(PermissionSeeder::definitions());

        return [
            'super_admin' => $allPermissions,
            'principal' => [
                'dashboard.view',
                'profile.manage_own',
                'students.view',
                'enrollments.view',
                'attendance.view',
                'report_cards.view',
                'report_cards.print',
                'student_daily_reports.view',
                'staff.view',
                'fee_categories.manage',
                'fee_items.manage',
                'fee_structures.manage',
                'fee_plans.manage',
                'discount_definitions.manage',
                'student_invoices.view',
                'student_payments.view',
                'external_exam_sessions.manage',
                'external_exam_payments.view',
                'wallets.view',
                'wallets.topup',
                'wallets.adjust',
                'wallet_transactions.view',
                'pos_products.manage',
                'pos_sales.create',
                'pos_sales.view',
                'pos_sales.reverse',
                'pos_reports.view',
                'reports.financial.view',
                'reports.receivables.view',
                'reports.academic.view',
                'audit_logs.finance.view',
                'audit_logs.academic.view',
                'audit_logs.settings.view',
                'rooms.manage',
                'academic_years.manage',
                'grades.manage',
                'sections.manage',
                'subjects.manage',
                'exams.manage',
                'id_cards.students.print',
                'id_cards.staff.print',
                'admin_settings.manage',
            ],
            'vice_principal' => [
                'dashboard.view',
                'profile.manage_own',
                'students.view',
                'students.update',
                'enrollments.view',
                'attendance.view',
                'report_cards.view',
                'report_cards.print',
                'student_daily_reports.view',
                'staff.view',
                'external_exam_sessions.manage',
                'reports.academic.view',
                'audit_logs.academic.view',
                'rooms.manage',
                'academic_years.manage',
                'grades.manage',
                'sections.manage',
                'subjects.manage',
                'exams.manage',
                'id_cards.students.print',
            ],
            'section_head' => [
                'dashboard.view',
                'profile.manage_own',
                'students.view',
                'students.update',
                'enrollments.view',
                'attendance.view',
                'marks.view',
                'marks.create',
                'marks.update',
                'marks.delete',
                'report_cards.view',
                'report_cards.print',
                'student_daily_reports.view',
                'student_daily_reports.delete',
                'external_exam_sessions.manage',
                'rooms.manage',
                'academic_years.manage',
                'exams.manage',
                'id_cards.students.print',
            ],
            'teacher' => [
                'dashboard.view',
                'profile.manage_own',
                'students.view',
                'attendance.view',
                'attendance.create',
                'attendance.update',
                'marks.view',
                'marks.create',
                'marks.update',
                'report_cards.view',
                'report_cards.print',
                'student_daily_reports.view',
                'student_daily_reports.create',
                'student_daily_reports.update',
            ],
            'registrar_cashier' => [
                'dashboard.view',
                'profile.manage_own',
                'students.view',
                'students.create',
                'students.update',
                'students.delete',
                'enrollments.view',
                'enrollments.create',
                'enrollments.update',
                'enrollments.delete',
                'attendance.view',
                'student_daily_reports.view',
                'student_invoices.view',
                'student_invoices.create',
                'student_invoices.issue',
                'student_invoices.print',
                'student_payments.view',
                'student_payments.collect',
                'external_exam_registrations.manage',
                'external_exam_payments.view',
                'external_exam_payments.collect',
                'id_cards.students.print',
            ],
            'finance_manager' => [
                'dashboard.view',
                'profile.manage_own',
                'students.view',
                'students.create',
                'students.update',
                'students.delete',
                'enrollments.view',
                'enrollments.create',
                'enrollments.update',
                'enrollments.delete',
                'attendance.view',
                'student_daily_reports.view',
                'fee_categories.manage',
                'fee_items.manage',
                'fee_structures.manage',
                'fee_plans.manage',
                'discount_definitions.manage',
                'student_discounts.manage',
                'student_invoices.view',
                'student_invoices.create',
                'student_invoices.issue',
                'student_invoices.void',
                'student_invoices.print',
                'student_payments.view',
                'student_payments.collect',
                'student_payments.delete',
                'external_exam_sessions.manage',
                'external_exam_registrations.manage',
                'external_exam_payments.view',
                'external_exam_payments.collect',
                'external_exam_payments.delete',
                'wallets.view',
                'wallets.topup',
                'wallets.adjust',
                'wallet_transactions.view',
                'pos_products.manage',
                'pos_sales.create',
                'pos_sales.view',
                'pos_sales.reverse',
                'pos_reports.view',
                'reports.financial.view',
                'reports.receivables.view',
                'audit_logs.finance.view',
            ],
            'pos_cashier' => [
                'dashboard.view',
                'profile.manage_own',
                'wallets.view',
                'wallets.topup',
                'pos_sales.create',
                'pos_sales.view',
            ],
            'hr_manager' => [
                'dashboard.view',
                'profile.manage_own',
                'students.view',
                'staff.view',
                'staff.create',
                'staff.update',
                'audit_logs.academic.view',
                'id_cards.staff.print',
            ],
            'librarian' => [
                'dashboard.view',
                'profile.manage_own',
            ],
            'operations_staff' => [
                'dashboard.view',
                'profile.manage_own',
            ],
            'staff_self_service' => [
                'dashboard.view',
                'profile.manage_own',
            ],
        ];
    }
}
