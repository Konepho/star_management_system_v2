<?php

use App\Models\Grade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('grades', 'grade_group')) {
            return;
        }

        Schema::table('grades', function (Blueprint $table) {
            $table->string('grade_group')->nullable()->after('code');
        });

        DB::table('grades')
            ->orderBy('id')
            ->get(['id', 'sort_order'])
            ->each(function (object $grade): void {
                DB::table('grades')
                    ->where('id', $grade->id)
                    ->update([
                        'grade_group' => $grade->sort_order >= 6
                            ? Grade::GROUP_SECONDARY
                            : Grade::GROUP_PRIMARY,
                    ]);
            });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('grades', function (Blueprint $table) {
                $table->string('grade_group')->default(Grade::GROUP_PRIMARY)->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('grades', 'grade_group')) {
            return;
        }

        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn('grade_group');
        });
    }
};
