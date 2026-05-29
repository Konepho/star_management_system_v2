<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('fee_structures')
            ->where('billing_cycle', 'termly')
            ->update(['billing_cycle' => 'quarterly']);
    }

    public function down(): void
    {
        DB::table('fee_structures')
            ->where('billing_cycle', 'quarterly')
            ->update(['billing_cycle' => 'termly']);
    }
};
