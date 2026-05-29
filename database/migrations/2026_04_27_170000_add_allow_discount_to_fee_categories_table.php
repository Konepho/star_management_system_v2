<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('fee_categories', 'allow_discount')) {
            return;
        }

        Schema::table('fee_categories', function (Blueprint $table) {
            $table->boolean('allow_discount')->default(true)->after('type');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('fee_categories', 'allow_discount')) {
            return;
        }

        Schema::table('fee_categories', function (Blueprint $table) {
            $table->dropColumn('allow_discount');
        });
    }
};
