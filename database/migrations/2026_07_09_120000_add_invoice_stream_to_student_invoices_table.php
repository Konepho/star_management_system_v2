<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_invoices', function (Blueprint $table) {
            $table->string('invoice_stream')->default('other')->after('billing_period_type');
        });

        DB::table('student_invoices')
            ->orderBy('id')
            ->get(['id', 'enrollment_id'])
            ->each(function (object $invoice): void {
                $academicItemCount = DB::table('student_invoice_items')
                    ->where('student_invoice_id', $invoice->id)
                    ->whereNotNull('fee_structure_id')
                    ->count();

                $extraClassItemCount = DB::table('student_invoice_items')
                    ->where('student_invoice_id', $invoice->id)
                    ->whereNotNull('student_extra_class_registration_id')
                    ->count();

                $otherItemCount = DB::table('student_invoice_items')
                    ->where('student_invoice_id', $invoice->id)
                    ->whereNull('fee_structure_id')
                    ->whereNull('student_extra_class_registration_id')
                    ->count();

                $streamFlags = collect([
                    $academicItemCount > 0,
                    $extraClassItemCount > 0,
                    $otherItemCount > 0,
                ])->filter()->count();

                $invoiceStream = match (true) {
                    $streamFlags > 1 => 'mixed',
                    $academicItemCount > 0 => 'academic',
                    $extraClassItemCount > 0 => 'extra_class',
                    $otherItemCount > 0 => 'other',
                    $invoice->enrollment_id !== null => 'academic',
                    default => 'other',
                };

                DB::table('student_invoices')
                    ->where('id', $invoice->id)
                    ->update([
                        'invoice_stream' => $invoiceStream,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('student_invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_stream');
        });
    }
};
