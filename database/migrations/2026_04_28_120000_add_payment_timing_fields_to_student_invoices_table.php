<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_invoices', function (Blueprint $table): void {
            $table->string('payment_timing_status', 50)->nullable()->after('status');
            $table->date('payment_timing_locked_on')->nullable()->after('payment_timing_status');
        });
    }

    public function down(): void
    {
        Schema::table('student_invoices', function (Blueprint $table): void {
            $table->dropColumn(['payment_timing_status', 'payment_timing_locked_on']);
        });
    }
};
