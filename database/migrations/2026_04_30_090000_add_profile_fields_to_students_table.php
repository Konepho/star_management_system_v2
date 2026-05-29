<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('name_mm')->nullable()->after('admission_no');
            $table->string('name_en')->nullable()->after('name_mm');
            $table->string('preferred_name')->nullable()->after('name_en');
            $table->string('student_type')->nullable()->after('gender');
            $table->string('previous_school_name')->nullable()->after('student_type');
            $table->string('email')->nullable()->after('admission_date');
            $table->string('contact_number')->nullable()->after('email');
            $table->string('emergency_contact_number')->nullable()->after('contact_number');
        });

        DB::table('students')
            ->select(['id', 'first_name', 'last_name', 'phone'])
            ->orderBy('id')
            ->get()
            ->each(function (object $student): void {
                $nameEn = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

                DB::table('students')
                    ->where('id', $student->id)
                    ->update([
                        'name_en' => $nameEn !== '' ? $nameEn : null,
                        'contact_number' => $student->phone,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'name_mm',
                'name_en',
                'preferred_name',
                'student_type',
                'previous_school_name',
                'email',
                'contact_number',
                'emergency_contact_number',
            ]);
        });
    }
};
