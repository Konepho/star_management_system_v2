<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_product_categories', function (Blueprint $table) {
            $table->string('pos_section')->default('snack_shop')->after('description')->index();
        });

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->string('pos_section')->default('snack_shop')->after('sale_no')->index();
        });

        if (DB::table('pos_products')->whereNull('pos_product_category_id')->exists()) {
            $defaultCategoryId = DB::table('pos_product_categories')
                ->where('code', 'GENERAL-SNACK')
                ->value('id');

            if (! $defaultCategoryId) {
                $defaultCategoryId = DB::table('pos_product_categories')->insertGetId([
                    'name' => 'General Snack Shop',
                    'code' => 'GENERAL-SNACK',
                    'description' => 'Automatically assigned to existing uncategorized POS products.',
                    'pos_section' => 'snack_shop',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('pos_products')
                ->whereNull('pos_product_category_id')
                ->update(['pos_product_category_id' => $defaultCategoryId]);
        }
    }

    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropIndex(['pos_section']);
            $table->dropColumn('pos_section');
        });

        Schema::table('pos_product_categories', function (Blueprint $table) {
            $table->dropIndex(['pos_section']);
            $table->dropColumn('pos_section');
        });
    }
};
