<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_invoice_discounts', function (Blueprint $table): void {
            $table->foreignId('discount_definition_id')
                ->nullable()
                ->after('student_invoice_item_id')
                ->constrained('discount_definitions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_invoice_discounts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('discount_definition_id');
        });
    }
};
