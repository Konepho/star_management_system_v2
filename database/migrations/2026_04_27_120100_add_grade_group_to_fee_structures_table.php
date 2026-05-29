<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('fee_structures', 'grade_group')) {
            return;
        }

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->string('grade_group')->nullable()->after('grade_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('fee_structures', 'grade_group')) {
            return;
        }

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropColumn('grade_group');
        });
    }
};
