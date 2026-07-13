<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_extra_class_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('extra_class_offering_id');
            $table->date('registration_date');
            $table->string('status')->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'extra_class_offering_id'], 'student_extra_class_unique');
            $table->foreign('student_id', 'secr_student_fk')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('academic_year_id', 'secr_academic_year_fk')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('extra_class_offering_id', 'secr_offering_fk')->references('id')->on('extra_class_offerings')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_extra_class_registrations');
    }
};
