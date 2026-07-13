<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_class_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extra_class_catalog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->decimal('fee_amount', 12, 2);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['extra_class_catalog_id', 'academic_year_id'], 'extra_class_offerings_catalog_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_class_offerings');
    }
};
