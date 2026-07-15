<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        'dashboard.view',
        'profile.manage_own',
        'wallets.view',
        'wallets.topup',
        'wallets.adjust',
        'wallet_transactions.view',
        'pos_products.manage',
        'pos_sales.create',
        'pos_sales.view',
        'pos_sales.reverse',
        'pos_reports.view',
    ];

    public function up(): void
    {
        $now = now();

        DB::table('roles')->insertOrIgnore([
            'name' => 'POS Manager',
            'slug' => 'pos_manager',
            'description' => 'Full management access to POS products, wallets, sales, reversals, and reports.',
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('roles')->where('slug', 'pos_manager')->update([
            'name' => 'POS Manager',
            'description' => 'Full management access to POS products, wallets, sales, reversals, and reports.',
            'is_system' => true,
            'updated_at' => $now,
        ]);

        $roleId = DB::table('roles')->where('slug', 'pos_manager')->value('id');
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', self::PERMISSIONS)
            ->pluck('id');

        $rows = $permissionIds->map(fn ($permissionId): array => [
            'permission_id' => $permissionId,
            'role_id' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('permission_role')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('slug', 'pos_manager')->value('id');

        if (! $roleId) {
            return;
        }

        DB::table('permission_role')->where('role_id', $roleId)->delete();
        DB::table('role_user')->where('role_id', $roleId)->delete();
        DB::table('roles')->where('id', $roleId)->delete();
    }
};
