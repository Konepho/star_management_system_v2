<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_exam_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('external_exam_session_id')->constrained()->cascadeOnDelete();
            $table->date('registration_date');
            $table->string('status')->default('registered');
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('candidate_no')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->string('grade')->nullable();
            $table->string('result_status')->default('pending');
            $table->text('result_remarks')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'external_exam_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_exam_registrations');
    }
};
