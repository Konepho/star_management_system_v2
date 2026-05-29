<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_invoice_items', function (Blueprint $table): void {
            $table->boolean('is_system_adjustment')->default(false)->after('remarks');
            $table->string('adjustment_code', 50)->nullable()->after('is_system_adjustment');
        });
    }

    public function down(): void
    {
        Schema::table('student_invoice_items', function (Blueprint $table): void {
            $table->dropColumn(['is_system_adjustment', 'adjustment_code']);
        });
    }
};
