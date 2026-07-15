<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $stationeryCategory = DB::table('pos_product_categories')
            ->where('code', 'STATIONERY')
            ->first();

        if ($stationeryCategory) {
            DB::table('pos_product_categories')
                ->where('id', $stationeryCategory->id)
                ->update([
                    'pos_section' => 'stationery',
                    'updated_at' => now(),
                ]);

            return;
        }

        if (! DB::table('pos_product_categories')->where('pos_section', 'stationery')->exists()) {
            DB::table('pos_product_categories')->insert([
                'name' => 'Stationery',
                'code' => 'STATIONERY',
                'description' => 'Pens, books, paper, and other stationery items.',
                'pos_section' => 'stationery',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $categoryId = DB::table('pos_product_categories')
            ->where('code', 'STATIONERY')
            ->value('id');

        if ($categoryId && ! DB::table('pos_products')->where('pos_product_category_id', $categoryId)->exists()) {
            DB::table('pos_product_categories')->where('id', $categoryId)->delete();
        }
    }
};
