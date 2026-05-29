<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_invoices', function (Blueprint $table) {
            $table->foreignId('enrollment_id')->nullable()->after('academic_year_id')->constrained()->nullOnDelete();
            $table->foreignId('fee_plan_id')->nullable()->after('enrollment_id')->constrained()->nullOnDelete();
            $table->foreignId('grade_id')->nullable()->after('fee_plan_id')->constrained()->nullOnDelete();
            $table->foreignId('section_id')->nullable()->after('grade_id')->constrained()->nullOnDelete();
            $table->string('billing_period_type')->default('academic_year')->after('status');
            $table->string('billing_month', 7)->nullable()->after('billing_period_type');
            $table->string('billing_quarter', 16)->nullable()->after('billing_month');
            $table->string('billing_year_label')->nullable()->after('billing_quarter');
            $table->timestamp('issued_at')->nullable()->after('payment_timing_locked_on');
            $table->timestamp('cancelled_at')->nullable()->after('issued_at');
            $table->timestamp('voided_at')->nullable()->after('cancelled_at');
        });

        DB::table('student_invoices')
            ->orderBy('id')
            ->get()
            ->each(function (object $invoice): void {
                $billingPeriodType = 'academic_year';
                $billingMonth = null;
                $billingQuarter = null;
                $billingYearLabel = null;
                $issuedAt = null;

                if ($invoice->status === 'draft') {
                    $billingPeriodType = 'custom';
                } elseif ($invoice->issue_date) {
                    $billingMonth = substr((string) $invoice->issue_date, 0, 7);
                    $billingYearLabel = $billingMonth;
                }

                if (in_array($invoice->status, ['issued', 'partial', 'paid', 'overdue'], true) && $invoice->issue_date) {
                    $issuedAt = $invoice->issue_date . ' 00:00:00';
                }

                DB::table('student_invoices')
                    ->where('id', $invoice->id)
                    ->update([
                        'billing_period_type' => $billingPeriodType,
                        'billing_month' => $billingMonth,
                        'billing_quarter' => $billingQuarter,
                        'billing_year_label' => $billingYearLabel,
                        'issued_at' => $issuedAt,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
            $table->dropConstrainedForeignId('grade_id');
            $table->dropConstrainedForeignId('fee_plan_id');
            $table->dropConstrainedForeignId('enrollment_id');
            $table->dropColumn([
                'billing_period_type',
                'billing_month',
                'billing_quarter',
                'billing_year_label',
                'issued_at',
                'cancelled_at',
                'voided_at',
            ]);
        });
    }
};
