<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Full access to all modules and settings.', 'is_system' => true],
            ['name' => 'Principal', 'slug' => 'principal', 'description' => 'School-wide leadership access.', 'is_system' => true],
            ['name' => 'Vice Principal', 'slug' => 'vice_principal', 'description' => 'Operational academic leadership access.', 'is_system' => true],
            ['name' => 'Section Head', 'slug' => 'section_head', 'description' => 'Section-level academic management access.', 'is_system' => true],
            ['name' => 'Teacher', 'slug' => 'teacher', 'description' => 'Teaching, attendance, and classroom academic access.', 'is_system' => true],
            ['name' => 'Registrar / Cashier', 'slug' => 'registrar_cashier', 'description' => 'Admissions, enrollment, invoicing, and payment collection access.', 'is_system' => true],
            ['name' => 'POS Cashier', 'slug' => 'pos_cashier', 'description' => 'Wallet top-up and POS sale access.', 'is_system' => true],
            ['name' => 'Finance Manager', 'slug' => 'finance_manager', 'description' => 'Finance setup, invoicing, payments, and reports access.', 'is_system' => true],
            ['name' => 'HR Manager', 'slug' => 'hr_manager', 'description' => 'Staff and HR management access.', 'is_system' => true],
            ['name' => 'Librarian', 'slug' => 'librarian', 'description' => 'Library-specific access.', 'is_system' => true],
            ['name' => 'Operations Staff', 'slug' => 'operations_staff', 'description' => 'Operations and facilities-related access.', 'is_system' => true],
            ['name' => 'Staff Self Service', 'slug' => 'staff_self_service', 'description' => 'Own profile and self-service access only.', 'is_system' => true],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role,
            );
        }
    }
}
