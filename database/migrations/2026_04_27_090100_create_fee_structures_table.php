<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained()->nullOnDelete();
            $table->string('grade_group')->nullable();
            $table->foreignId('fee_category_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('billing_cycle')->default('monthly');
            $table->boolean('is_optional')->default(false);
            $table->string('status')->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'grade_id', 'fee_category_id', 'billing_cycle'], 'fee_structures_unique_setup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
