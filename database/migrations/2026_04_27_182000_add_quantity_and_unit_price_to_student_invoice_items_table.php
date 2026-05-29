<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('student_invoice_items', 'quantity')) {
            Schema::table('student_invoice_items', function (Blueprint $table) {
                $table->unsignedInteger('quantity')->default(1)->after('installment_no');
            });
        }

        if (! Schema::hasColumn('student_invoice_items', 'unit_price')) {
            Schema::table('student_invoice_items', function (Blueprint $table) {
                $table->decimal('unit_price', 12, 2)->default(0)->after('quantity');
            });
        }

        DB::table('student_invoice_items')
            ->where('unit_price', 0)
            ->update([
                'unit_price' => DB::raw('amount'),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('student_invoice_items', 'unit_price')) {
            Schema::table('student_invoice_items', function (Blueprint $table) {
                $table->dropColumn('unit_price');
            });
        }

        if (Schema::hasColumn('student_invoice_items', 'quantity')) {
            Schema::table('student_invoice_items', function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }
    }
};
