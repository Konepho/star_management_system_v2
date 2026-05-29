<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('installment_no');
            $table->decimal('amount', 12, 2);
            $table->date('due_date')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->unique(['fee_structure_id', 'installment_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_installments');
    }
};
