<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
            $table->dropConstrainedForeignId('grade_id');
            $table->dropConstrainedForeignId('academic_year_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('grade_id')->nullable()->after('academic_year_id')->constrained()->nullOnDelete();
            $table->foreignId('section_id')->nullable()->after('grade_id')->constrained()->nullOnDelete();
        });
    }
};
