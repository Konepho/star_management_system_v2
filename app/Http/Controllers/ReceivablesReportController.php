<?php

namespace App\Http\Controllers;

use App\Models\StudentInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReceivablesReportController extends Controller
{
    protected const REPORTABLE_INVOICE_STATUSES = [
        StudentInvoice::STATUS_ISSUED,
        StudentInvoice::STATUS_PARTIAL,
    ];

    public function __invoke(Request $request): View
    {
        $viewMode = in_array($request->string('view')->value(), ['monthly', 'yearly'], true)
            ? $request->string('view')->value()
            : 'monthly';
        $selectedYear = max(2000, (int) $request->integer('year', now()->year));
        $selectedMonth = min(12, max(1, (int) $request->integer('month', now()->month)));
        $today = Carbon::today();

        $openInvoices = StudentInvoice::query()
            ->with(['student', 'academicYear', 'grade', 'section', 'payments', 'discounts', 'items'])
            ->whereIn('status', self::REPORTABLE_INVOICE_STATUSES)
            ->get()
            ->filter(fn (StudentInvoice $invoice) => $invoice->balance_due > 0)
            ->values();

        $dueInvoices = $openInvoices
            ->filter(function (StudentInvoice $invoice) use ($viewMode, $selectedYear, $selectedMonth): bool {
                if (! $invoice->due_date) {
                    return false;
                }

                if ($viewMode === 'yearly') {
                    return (int) $invoice->due_date->format('Y') === $selectedYear;
                }

                return (int) $invoice->due_date->format('Y') === $selectedYear
                    && (int) $invoice->due_date->format('n') === $selectedMonth;
            })
            ->sortBy('due_date')
            ->values();

        $overdueInvoices = $openInvoices
            ->filter(fn (StudentInvoice $invoice) => $invoice->due_date && $invoice->due_date->lt($today))
            ->values();
        $futureDueInvoices = $openInvoices
            ->filter(fn (StudentInvoice $invoice) => $invoice->due_date && $invoice->due_date->gt($today))
            ->values();

        $currentPeriodKey = $viewMode === 'yearly'
            ? (string) $selectedYear
            : sprintf('%04d-%02d', $selectedYear, $selectedMonth);
        $currentPeriodLabel = $viewMode === 'yearly'
            ? (string) $selectedYear
            : Carbon::create($selectedYear, $selectedMonth, 1)->format('F Y');
        $periodInvoicedAmount = (float) $dueInvoices->sum('total_amount');
        $periodCollectedAmount = (float) $dueInvoices->sum(fn (StudentInvoice $invoice) => $invoice->paid_amount);
        $periodOutstanding = (float) $dueInvoices->sum(fn (StudentInvoice $invoice) => $invoice->balance_due);
        $collectionGap = max(0, $periodInvoicedAmount - $periodCollectedAmount);
        $collectionRate = $periodInvoicedAmount > 0
            ? round(($periodCollectedAmount / $periodInvoicedAmount) * 100, 1)
            : 0.0;
        $ageingSummary = $this->buildAgeingSummary($overdueInvoices, $today);

        return view('reports.receivables', [
            'viewMode' => $viewMode,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'dueInvoices' => $dueInvoices,
            'totalOutstanding' => (float) $openInvoices->sum(fn (StudentInvoice $invoice) => $invoice->balance_due),
            'periodOutstanding' => $periodOutstanding,
            'futureDueAmount' => (float) $futureDueInvoices->sum(fn (StudentInvoice $invoice) => $invoice->balance_due),
            'overdueInvoices' => $overdueInvoices,
            'overdueAmount' => (float) $overdueInvoices->sum(fn (StudentInvoice $invoice) => $invoice->balance_due),
            'ageingSummary' => $ageingSummary,
            'currentPeriodLabel' => $currentPeriodLabel,
            'periodInvoicedAmount' => $periodInvoicedAmount,
            'periodCollectedAmount' => $periodCollectedAmount,
            'collectionGap' => $collectionGap,
            'collectionRate' => $collectionRate,
            'availableYears' => collect($openInvoices)
                ->filter(fn (StudentInvoice $invoice) => $invoice->due_date !== null)
                ->map(fn (StudentInvoice $invoice) => (int) $invoice->due_date->format('Y'))
                ->push(now()->year)
                ->unique()
                ->sort()
                ->values(),
        ]);
    }

    protected function buildAgeingSummary(Collection $overdueInvoices, Carbon $today): Collection
    {
        $bands = [
            ['key' => '1_30', 'label' => '1-30 Days', 'min' => 1, 'max' => 30],
            ['key' => '31_60', 'label' => '31-60 Days', 'min' => 31, 'max' => 60],
            ['key' => '61_90', 'label' => '61-90 Days', 'min' => 61, 'max' => 90],
            ['key' => '90_plus', 'label' => '90+ Days', 'min' => 91, 'max' => null],
        ];

        return collect($bands)->map(function (array $band) use ($overdueInvoices, $today) {
            $matchingInvoices = $overdueInvoices->filter(function (StudentInvoice $invoice) use ($band, $today): bool {
                if (! $invoice->due_date) {
                    return false;
                }

                $daysOverdue = $invoice->due_date->diffInDays($today);

                if ($daysOverdue < $band['min']) {
                    return false;
                }

                if ($band['max'] !== null && $daysOverdue > $band['max']) {
                    return false;
                }

                return true;
            })->values();

            return [
                'key' => $band['key'],
                'label' => $band['label'],
                'invoice_count' => $matchingInvoices->count(),
                'amount' => (float) $matchingInvoices->sum(fn (StudentInvoice $invoice) => $invoice->balance_due),
            ];
        })->values();
    }
}
