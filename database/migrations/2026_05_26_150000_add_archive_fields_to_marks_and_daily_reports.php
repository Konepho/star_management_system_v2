<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marks', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('remarks');
        });

        Schema::table('student_daily_reports', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('remark');
        });
    }

    public function down(): void
    {
        Schema::table('student_daily_reports', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });

        Schema::table('marks', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
