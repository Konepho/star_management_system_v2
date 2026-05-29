<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropStudentSessionIndexes();

        Schema::table('external_exam_registrations', function (Blueprint $table) {
            $table->index(['student_id', 'external_exam_session_id'], 'external_exam_registrations_student_session_index');
        });
    }

    public function down(): void
    {
        Schema::table('external_exam_registrations', function (Blueprint $table) {
            $table->dropIndex('external_exam_registrations_student_session_index');
            $table->unique(['student_id', 'external_exam_session_id']);
        });
    }

    private function dropStudentSessionIndexes(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            Schema::table('external_exam_registrations', function (Blueprint $table) {
                $table->dropUnique(['student_id', 'external_exam_session_id']);
            });

            return;
        }

        $rows = collect(DB::select('SHOW INDEX FROM external_exam_registrations'))
            ->groupBy('Key_name');

        foreach ($rows as $indexName => $indexRows) {
            if ($indexName === 'PRIMARY') {
                continue;
            }

            $columns = collect($indexRows)
                ->sortBy('Seq_in_index')
                ->pluck('Column_name')
                ->values()
                ->all();

            if ($columns === ['student_id', 'external_exam_session_id']) {
                DB::statement(sprintf('ALTER TABLE external_exam_registrations DROP INDEX `%s`', $indexName));
            }
        }
    }
};
