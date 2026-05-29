<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(RolePermissionSeeder::class);

        $admin = User::updateOrCreate([
            'username' => 'admin',
        ], [
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
            'phone' => '0900000000',
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $adminRole = Role::where('slug', 'super_admin')->first();

        if ($adminRole) {
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        User::updateOrCreate(
            ['username' => 'finance.demo'],
            [
                'name' => 'Finance Test User',
                'email' => 'test@example.com',
                'phone' => '0911111111',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );
    }
}
