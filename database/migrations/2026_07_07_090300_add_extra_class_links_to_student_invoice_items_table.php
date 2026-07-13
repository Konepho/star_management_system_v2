<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_invoice_items', function (Blueprint $table) {
            $table->unsignedBigInteger('extra_class_offering_id')->nullable()->after('fee_item_id');
            $table->unsignedBigInteger('student_extra_class_registration_id')->nullable()->after('extra_class_offering_id');
            $table->foreign('extra_class_offering_id', 'sii_extra_class_offering_fk')->references('id')->on('extra_class_offerings')->nullOnDelete();
            $table->foreign('student_extra_class_registration_id', 'sii_extra_class_registration_fk')->references('id')->on('student_extra_class_registrations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_invoice_items', function (Blueprint $table) {
            $table->dropForeign('sii_extra_class_registration_fk');
            $table->dropForeign('sii_extra_class_offering_fk');
            $table->dropColumn(['student_extra_class_registration_id', 'extra_class_offering_id']);
        });
    }
};
