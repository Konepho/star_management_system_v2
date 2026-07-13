<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\StudentExtraClassRegistration;
use App\Models\StudentInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    protected const REPORTABLE_INVOICE_STATUSES = [
        StudentInvoice::STATUS_ISSUED,
        StudentInvoice::STATUS_PARTIAL,
        StudentInvoice::STATUS_PAID,
    ];

    public function __invoke(Request $request): View
    {
        $academicYears = AcademicYear::query()
            ->orderByDesc('start_date')
            ->get();

        $selectedAcademicYear = $academicYears->firstWhere('id', (int) $request->integer('academic_year_id'))
            ?? $academicYears->firstWhere('is_current', true)
            ?? $academicYears->first();

        abort_unless($selectedAcademicYear, 404, 'No academic years found.');

        $period = in_array($request->string('period')->value(), ['month', 'quarter', 'academic_year'], true)
            ? $request->string('period')->value()
            : 'academic_year';

        $academicMonths = $this->buildAcademicMonths($selectedAcademicYear);
        $monthOptions = $academicMonths->pluck('label', 'key');
        $selectedMonthKey = $monthOptions->has($request->string('month')->value())
            ? $request->string('month')->value()
            : $monthOptions->keys()->first();

        $quarterOptions = $this->buildQuarterOptions($academicMonths);
        $selectedQuarterKey = $quarterOptions->pluck('label', 'key')->has($request->string('quarter')->value())
            ? $request->string('quarter')->value()
            : $quarterOptions->first()['key'];

        $enrollments = Enrollment::query()
            ->with(['student', 'grade', 'section', 'feePlan.feeStructures.installments'])
            ->where('academic_year_id', $selectedAcademicYear->id)
            ->where('status', 'active')
            ->whereHas('student', fn ($query) => $query->where('status', 'active'))
            ->get();

        $feeStructures = FeeStructure::query()
            ->with(['feeCategory', 'installments'])
            ->where('academic_year_id', $selectedAcademicYear->id)
            ->where('status', 'active')
            ->where('is_optional', false)
            ->get();
        $extraRegistrations = StudentExtraClassRegistration::query()
            ->with(['student', 'offering'])
            ->where('academic_year_id', $selectedAcademicYear->id)
            ->where('status', StudentExtraClassRegistration::STATUS_ACTIVE)
            ->whereHas('student', fn ($query) => $query->where('status', 'active'))
            ->get();

        $projection = $this->buildProjectionData($enrollments, $feeStructures, $extraRegistrations, $academicMonths);

        $invoices = StudentInvoice::query()
            ->with(['student', 'grade', 'payments', 'discounts'])
            ->where('academic_year_id', $selectedAcademicYear->id)
            ->whereIn('status', self::REPORTABLE_INVOICE_STATUSES)
            ->get();

        $students = $this->buildReportStudents($enrollments, $extraRegistrations);
        $studentMix = $this->buildStudentMixData($enrollments, $extraRegistrations);

        $actuals = $this->buildActualData($invoices, $academicMonths);
        $selectedMonthKeys = $this->selectedPeriodMonthKeys($period, $selectedMonthKey, $selectedQuarterKey, $academicMonths, $quarterOptions);

        $projectedForPeriod = $projection['monthly']
            ->only($selectedMonthKeys)
            ->sum();
        $billedForPeriod = $actuals['billedMonthly']
            ->only($selectedMonthKeys)
            ->sum();
        $collectedForPeriod = $actuals['collectedMonthly']
            ->only($selectedMonthKeys)
            ->sum();
        $discountsForPeriod = $actuals['discountMonthly']
            ->only($selectedMonthKeys)
            ->sum();
        $collectionGap = max(0, $projectedForPeriod - $collectedForPeriod);
        $collectionRate = $projectedForPeriod > 0
            ? round(($collectedForPeriod / $projectedForPeriod) * 100, 1)
            : 0.0;
        $streamSummaries = $this->buildStreamSummaries($invoices);

        $gradeSummaries = $this->buildGradeSummaries($enrollments, $extraRegistrations, $projection['gradeProjected'], $invoices);
        $chartData = $academicMonths->map(function (array $month) use ($projection, $actuals) {
            return [
                'label' => $month['short_label'],
                'projected' => (float) ($projection['monthly'][$month['key']] ?? 0),
                'billed' => (float) ($actuals['billedMonthly'][$month['key']] ?? 0),
                'collected' => (float) ($actuals['collectedMonthly'][$month['key']] ?? 0),
            ];
        })->values();
        $chartMax = max(1, (float) $chartData->max(fn (array $point) => max($point['projected'], $point['billed'], $point['collected'])));
        $cashTimelineData = $this->buildCashTimelineData($invoices);
        $cashTimelineMax = max(1, (float) $cashTimelineData->max(fn (array $point) => $point['collected'] ?? 0));

        return view('reports.financial', [
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'period' => $period,
            'monthOptions' => $monthOptions,
            'selectedMonthKey' => $selectedMonthKey,
            'quarterOptions' => $quarterOptions,
            'selectedQuarterKey' => $selectedQuarterKey,
            'periodLabel' => $this->periodLabel($period, $selectedMonthKey, $selectedQuarterKey, $monthOptions, $quarterOptions, $selectedAcademicYear),
            'studentsCount' => $students->count(),
            'academicStudentsCount' => $studentMix['academic_students_count'],
            'extraClassOnlyStudentsCount' => $studentMix['extra_class_only_students_count'],
            'projectedAnnualIncome' => (float) $projection['monthly']->sum(),
            'projectedAcademicAnnualIncome' => (float) collect($projection['streamProjected']['academic'])->sum(),
            'projectedExtraClassAnnualIncome' => (float) collect($projection['streamProjected']['extra_class'])->sum(),
            'projectedForPeriod' => (float) $projectedForPeriod,
            'billedForPeriod' => (float) $billedForPeriod,
            'collectedForPeriod' => (float) $collectedForPeriod,
            'discountsForPeriod' => (float) $discountsForPeriod,
            'collectionGap' => (float) $collectionGap,
            'collectionRate' => (float) $collectionRate,
            'outstandingForAcademicYear' => (float) $invoices->sum(fn (StudentInvoice $invoice) => $invoice->balance_due),
            'streamSummaries' => $streamSummaries,
            'gradeSummaries' => $gradeSummaries,
            'chartData' => $chartData,
            'chartMax' => $chartMax,
            'cashTimelineData' => $cashTimelineData,
            'cashTimelineMax' => $cashTimelineMax,
        ]);
    }

    protected function buildAcademicMonths(AcademicYear $academicYear): Collection
    {
        $months = collect();
        $cursor = $academicYear->start_date->copy()->startOfMonth();
        $end = $academicYear->end_date->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $months->push([
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->format('F Y'),
                'short_label' => $cursor->format('M'),
                'date' => $cursor->copy(),
            ]);

            $cursor->addMonth();
        }

        return $months;
    }

    protected function buildQuarterOptions(Collection $academicMonths): Collection
    {
        return $academicMonths
            ->values()
            ->chunk(3)
            ->values()
            ->map(function (Collection $months, int $index) {
                $first = $months->first();
                $last = $months->last();

                return [
                    'key' => 'q' . ($index + 1),
                    'label' => 'Quarter ' . ($index + 1) . ' (' . $first['date']->format('M') . ' - ' . $last['date']->format('M') . ')',
                    'months' => $months->pluck('key')->values()->all(),
                ];
            });
    }

    protected function buildProjectionData(Collection $enrollments, Collection $feeStructures, Collection $extraRegistrations, Collection $academicMonths): array
    {
        $monthlyTotals = $academicMonths->mapWithKeys(fn (array $month) => [$month['key'] => 0.0]);
        $streamProjected = [
            'academic' => $academicMonths->mapWithKeys(fn (array $month) => [$month['key'] => 0.0]),
            'extra_class' => $academicMonths->mapWithKeys(fn (array $month) => [$month['key'] => 0.0]),
        ];
        $gradeProjected = [];

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;

            if (! $student) {
                continue;
            }

            $studentStructures = $this->applicableFeeStructuresForEnrollment($enrollment, $feeStructures);

            foreach ($studentStructures as $feeStructure) {
                $allocation = $this->allocateFeeStructureAcrossAcademicMonths($student, $enrollment, $feeStructure, $academicMonths);

                foreach ($allocation as $monthKey => $amount) {
                    $monthlyTotals[$monthKey] = (float) $monthlyTotals[$monthKey] + (float) $amount;
                    $streamProjected['academic'][$monthKey] = (float) $streamProjected['academic'][$monthKey] + (float) $amount;
                }

                $gradeId = $enrollment->grade_id ?? 0;
                $gradeProjected[$gradeId] = ($gradeProjected[$gradeId] ?? 0) + array_sum($allocation);
            }
        }

        foreach ($extraRegistrations as $registration) {
            $offering = $registration->offering;

            if (! $offering) {
                continue;
            }

            $monthKeys = $academicMonths->pluck('key')->all();
            $registrationMonthKey = optional($registration->registration_date)->format('Y-m');
            $targetMonthKey = in_array($registrationMonthKey, $monthKeys, true)
                ? $registrationMonthKey
                : $monthKeys[0];

            $monthlyTotals[$targetMonthKey] = (float) $monthlyTotals[$targetMonthKey] + (float) $offering->fee_amount;
            $streamProjected['extra_class'][$targetMonthKey] = (float) $streamProjected['extra_class'][$targetMonthKey] + (float) $offering->fee_amount;

            $matchingEnrollment = $enrollments->first(function (Enrollment $enrollment) use ($registration): bool {
                return (int) $enrollment->student_id === (int) $registration->student_id;
            });
            $gradeId = $matchingEnrollment?->grade_id
                ?? $registration->student?->grade_id
                ?? 0;
            $gradeProjected[$gradeId] = ($gradeProjected[$gradeId] ?? 0) + (float) $offering->fee_amount;
        }

        return [
            'monthly' => $monthlyTotals,
            'streamProjected' => $streamProjected,
            'gradeProjected' => $gradeProjected,
        ];
    }

    protected function applicableFeeStructuresForEnrollment(Enrollment $enrollment, Collection $feeStructures): Collection
    {
        if ($enrollment->relationLoaded('feePlan') && $enrollment->feePlan) {
            return $enrollment->feePlan->feeStructures
                ->where('status', 'active')
                ->where('is_optional', false)
                ->values();
        }

        return $feeStructures->filter(function (FeeStructure $feeStructure) use ($enrollment): bool {
            if ($feeStructure->grade_id === null && $feeStructure->grade_group === null) {
                return true;
            }

            if ($feeStructure->grade_id !== null) {
                return (int) $feeStructure->grade_id === (int) $enrollment->grade_id;
            }

            return $enrollment->grade?->grade_group
                && $feeStructure->grade_group === $enrollment->grade->grade_group;
        })->values();
    }

    protected function allocateFeeStructureAcrossAcademicMonths(Student $student, Enrollment $enrollment, FeeStructure $feeStructure, Collection $academicMonths): array
    {
        $monthKeys = $academicMonths->pluck('key')->all();
        $amounts = array_fill_keys($monthKeys, 0.0);

        if ($feeStructure->billing_cycle === 'monthly') {
            foreach ($monthKeys as $monthKey) {
                $amounts[$monthKey] += (float) $feeStructure->amount;
            }

            return $amounts;
        }

        if ($feeStructure->billing_cycle === 'quarterly') {
            $quarterChunks = array_chunk($monthKeys, 3);

            foreach ($quarterChunks as $chunk) {
                if ($chunk === []) {
                    continue;
                }

                $perMonthAmount = (float) $feeStructure->amount / count($chunk);

                foreach ($chunk as $monthKey) {
                    $amounts[$monthKey] += $perMonthAmount;
                }
            }

            return $amounts;
        }

        if ($feeStructure->billing_cycle === 'annual') {
            $amounts[$monthKeys[0]] += (float) $feeStructure->amount;

            return $amounts;
        }

        if ($feeStructure->billing_cycle === 'one-time') {
            $enrollmentMonthKey = optional($enrollment->enrollment_date)->format('Y-m');
            $targetMonthKey = in_array($enrollmentMonthKey, $monthKeys, true)
                ? $enrollmentMonthKey
                : $monthKeys[0];
            $amounts[$targetMonthKey] += (float) $feeStructure->amount;

            return $amounts;
        }

        if ($feeStructure->billing_cycle === 'installment') {
            foreach ($feeStructure->installments as $installment) {
                $installmentMonthKey = optional($installment->due_date)->format('Y-m');
                $targetMonthKey = in_array($installmentMonthKey, $monthKeys, true)
                    ? $installmentMonthKey
                    : $monthKeys[0];

                $amounts[$targetMonthKey] += (float) $installment->amount;
            }
        }

        return $amounts;
    }

    protected function buildActualData(Collection $invoices, Collection $academicMonths): array
    {
        $monthKeys = $academicMonths->pluck('key')->all();
        $billedMonthly = collect(array_fill_keys($monthKeys, 0.0));
        $collectedMonthly = collect(array_fill_keys($monthKeys, 0.0));
        $discountMonthly = collect(array_fill_keys($monthKeys, 0.0));

        foreach ($invoices as $invoice) {
            $issueMonthKey = optional($invoice->issue_date)->format('Y-m');

            if ($issueMonthKey && $billedMonthly->has($issueMonthKey)) {
                $billedMonthly[$issueMonthKey] = (float) $billedMonthly[$issueMonthKey] + (float) $invoice->total_amount;
                $discountMonthly[$issueMonthKey] = (float) $discountMonthly[$issueMonthKey] + (float) $invoice->discount_amount;
            }

            foreach ($invoice->payments as $payment) {
                if ($payment->isReversed()) {
                    continue;
                }

                $paymentMonthKey = optional($payment->payment_date)->format('Y-m');

                if ($paymentMonthKey && $collectedMonthly->has($paymentMonthKey)) {
                    $collectedMonthly[$paymentMonthKey] = (float) $collectedMonthly[$paymentMonthKey] + (float) $payment->amount;
                }
            }
        }

        return [
            'billedMonthly' => $billedMonthly,
            'collectedMonthly' => $collectedMonthly,
            'discountMonthly' => $discountMonthly,
        ];
    }

    protected function buildCashTimelineData(Collection $invoices): Collection
    {
        $paymentMonths = collect();

        foreach ($invoices as $invoice) {
            foreach ($invoice->payments as $payment) {
                if ($payment->isReversed() || ! $payment->payment_date) {
                    continue;
                }

                $paymentMonths->push($payment->payment_date->copy()->startOfMonth());
            }
        }

        if ($paymentMonths->isEmpty()) {
            return collect();
        }

        $start = $paymentMonths->min()->copy()->startOfMonth();
        $end = $paymentMonths->max()->copy()->startOfMonth();
        $timeline = collect();
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $monthKey = $cursor->format('Y-m');
            $collected = 0.0;

            foreach ($invoices as $invoice) {
                foreach ($invoice->payments as $payment) {
                    if ($payment->isReversed() || ! $payment->payment_date) {
                        continue;
                    }

                    if ($payment->payment_date->format('Y-m') === $monthKey) {
                        $collected += (float) $payment->amount;
                    }
                }
            }

            $timeline->push([
                'key' => $monthKey,
                'label' => $cursor->format('M Y'),
                'collected' => $collected,
            ]);

            $cursor->addMonth();
        }

        return $timeline;
    }

    protected function selectedPeriodMonthKeys(
        string $period,
        string $selectedMonthKey,
        string $selectedQuarterKey,
        Collection $academicMonths,
        Collection $quarterOptions,
    ): array {
        if ($period === 'month') {
            return [$selectedMonthKey];
        }

        if ($period === 'quarter') {
            return $quarterOptions->firstWhere('key', $selectedQuarterKey)['months'] ?? [];
        }

        return $academicMonths->pluck('key')->all();
    }

    protected function periodLabel(
        string $period,
        string $selectedMonthKey,
        string $selectedQuarterKey,
        Collection $monthOptions,
        Collection $quarterOptions,
        AcademicYear $academicYear,
    ): string {
        if ($period === 'month') {
            return $monthOptions[$selectedMonthKey] ?? $academicYear->name;
        }

        if ($period === 'quarter') {
            return $quarterOptions->firstWhere('key', $selectedQuarterKey)['label'] ?? $academicYear->name;
        }

        return $academicYear->name;
    }

    protected function buildGradeSummaries(Collection $enrollments, Collection $extraRegistrations, array $gradeProjected, Collection $invoices): Collection
    {
        $gradeBuckets = [];

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;

            if (! $student) {
                continue;
            }

            $gradeId = $enrollment->grade_id ?? $student->grade_id ?? 0;
            $gradeBuckets[$gradeId] ??= [
                'grade_name' => $enrollment->grade?->name ?? $student->grade?->name ?? $this->extraClassOnlyGradeLabel(),
                'grade_group' => $enrollment->grade?->grade_group_label ?? $student->grade?->grade_group_label,
                'student_ids' => [],
            ];
            $gradeBuckets[$gradeId]['student_ids'][(int) $student->id] = true;
        }

        foreach ($extraRegistrations as $registration) {
            $student = $registration->student;

            if (! $student) {
                continue;
            }

            $matchingEnrollment = $enrollments->first(fn (Enrollment $enrollment) => (int) $enrollment->student_id === (int) $registration->student_id);
            $gradeId = $matchingEnrollment?->grade_id ?? $student->grade_id ?? 0;

            $gradeBuckets[$gradeId] ??= [
                'grade_name' => $matchingEnrollment?->grade?->name ?? $student->grade?->name ?? $this->extraClassOnlyGradeLabel(),
                'grade_group' => $matchingEnrollment?->grade?->grade_group_label ?? $student->grade?->grade_group_label,
                'student_ids' => [],
            ];
            $gradeBuckets[$gradeId]['student_ids'][(int) $student->id] = true;
        }

        $grades = collect($gradeBuckets)
            ->map(function (array $bucket, $gradeId) use ($gradeProjected, $invoices) {
                $studentIds = array_map('intval', array_keys($bucket['student_ids']));
                $gradeInvoices = $invoices->filter(fn (StudentInvoice $invoice) => in_array((int) $invoice->student_id, $studentIds, true));

                return [
                    'grade_name' => $bucket['grade_name'],
                    'grade_group' => $bucket['grade_group'],
                    'students_count' => count($studentIds),
                    'projected_amount' => (float) ($gradeProjected[$gradeId] ?? 0),
                    'billed_amount' => (float) $gradeInvoices->sum('total_amount'),
                    'collected_amount' => (float) $gradeInvoices->sum(fn (StudentInvoice $invoice) => $invoice->paid_amount),
                    'outstanding_amount' => (float) $gradeInvoices->sum(fn (StudentInvoice $invoice) => $invoice->balance_due),
                ];
            })
            ->values()
            ->sortBy('grade_name')
            ->values();

        return $grades;
    }

    protected function buildReportStudents(Collection $enrollments, Collection $extraRegistrations): Collection
    {
        $students = [];

        foreach ($enrollments as $enrollment) {
            if ($enrollment->student) {
                $students[(int) $enrollment->student->id] = $enrollment->student;
            }
        }

        foreach ($extraRegistrations as $registration) {
            if ($registration->student) {
                $students[(int) $registration->student->id] = $registration->student;
            }
        }

        return collect($students)->values();
    }

    protected function buildStudentMixData(Collection $enrollments, Collection $extraRegistrations): array
    {
        $academicStudentIds = $enrollments
            ->pluck('student_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $extraStudentIds = $extraRegistrations
            ->pluck('student_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $extraClassOnlyStudentsCount = $extraStudentIds
            ->reject(fn (int $studentId) => $academicStudentIds->contains($studentId))
            ->count();

        return [
            'academic_students_count' => $academicStudentIds->count(),
            'extra_class_only_students_count' => $extraClassOnlyStudentsCount,
        ];
    }

    protected function buildStreamSummaries(Collection $invoices): Collection
    {
        $streamLabels = StudentInvoice::invoiceStreamOptions();

        return collect([
            StudentInvoice::STREAM_ACADEMIC,
            StudentInvoice::STREAM_EXTRA_CLASS,
            StudentInvoice::STREAM_MIXED,
            StudentInvoice::STREAM_OTHER,
        ])->map(function (string $stream) use ($invoices, $streamLabels) {
            $streamInvoices = $invoices->filter(fn (StudentInvoice $invoice) => $this->normalizeInvoiceStream($invoice) === $stream);

            return [
                'key' => $stream,
                'label' => $streamLabels[$stream] ?? ucfirst(str_replace('_', ' ', $stream)),
                'invoice_count' => $streamInvoices->count(),
                'billed_amount' => (float) $streamInvoices->sum('total_amount'),
                'collected_amount' => (float) $streamInvoices->sum(fn (StudentInvoice $invoice) => $invoice->paid_amount),
                'outstanding_amount' => (float) $streamInvoices->sum(fn (StudentInvoice $invoice) => $invoice->balance_due),
            ];
        })->values();
    }

    protected function normalizeInvoiceStream(StudentInvoice $invoice): string
    {
        if (is_string($invoice->invoice_stream) && $invoice->invoice_stream !== '') {
            return $invoice->invoice_stream;
        }

        if ($invoice->enrollment_id) {
            return StudentInvoice::STREAM_ACADEMIC;
        }

        return StudentInvoice::STREAM_OTHER;
    }

    protected function extraClassOnlyGradeLabel(): string
    {
        return 'Extra Class Only Student';
    }
}
