<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('exam_body');
            $table->string('level')->nullable();
            $table->date('exam_date')->nullable();
            $table->date('registration_deadline')->nullable();
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->string('status')->default('open');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_exam_sessions');
    }
};
