<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('student_invoice_items', 'fee_item_id')) {
            return;
        }

        Schema::table('student_invoice_items', function (Blueprint $table) {
            $table->foreignId('fee_item_id')->nullable()->after('fee_structure_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('student_invoice_items', 'fee_item_id')) {
            return;
        }

        Schema::table('student_invoice_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_item_id');
        });
    }
};
