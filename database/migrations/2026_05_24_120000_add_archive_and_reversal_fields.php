<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('status');
        });

        Schema::table('student_payments', function (Blueprint $table) {
            $table->timestamp('reversed_at')->nullable()->after('notes');
            $table->text('reversal_reason')->nullable()->after('reversed_at');
        });

        Schema::table('external_exam_payments', function (Blueprint $table) {
            $table->timestamp('reversed_at')->nullable()->after('notes');
            $table->text('reversal_reason')->nullable()->after('reversed_at');
        });
    }

    public function down(): void
    {
        Schema::table('external_exam_payments', function (Blueprint $table) {
            $table->dropColumn(['reversed_at', 'reversal_reason']);
        });

        Schema::table('student_payments', function (Blueprint $table) {
            $table->dropColumn(['reversed_at', 'reversal_reason']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
