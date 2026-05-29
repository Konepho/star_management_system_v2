<?php

namespace App\Http\Controllers;

use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReceivablesReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $viewMode = in_array($request->string('view')->value(), ['monthly', 'yearly'], true)
            ? $request->string('view')->value()
            : 'monthly';
        $selectedYear = max(2000, (int) $request->integer('year', now()->year));
        $selectedMonth = min(12, max(1, (int) $request->integer('month', now()->month)));

        $openInvoices = StudentInvoice::query()
            ->with(['student', 'academicYear', 'grade', 'section', 'payments', 'discounts', 'items'])
            ->whereNotIn('status', ['paid', 'cancelled', 'draft'])
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

        $today = Carbon::today();
        $overdueInvoices = $openInvoices
            ->filter(fn (StudentInvoice $invoice) => $invoice->due_date && $invoice->due_date->lt($today))
            ->values();

        $expectedByMonth = $openInvoices
            ->filter(fn (StudentInvoice $invoice) => $invoice->due_date !== null)
            ->groupBy(fn (StudentInvoice $invoice) => $invoice->due_date->format('Y-m'))
            ->map(fn ($invoices, $period) => [
                'period' => $period,
                'label' => now()->createFromFormat('Y-m', $period)->format('F Y'),
                'amount' => (float) collect($invoices)->sum(fn (StudentInvoice $invoice) => $invoice->balance_due),
                'count' => count($invoices),
            ])
            ->sortKeys()
            ->values();

        $expectedByYear = $openInvoices
            ->filter(fn (StudentInvoice $invoice) => $invoice->due_date !== null)
            ->groupBy(fn (StudentInvoice $invoice) => $invoice->due_date->format('Y'))
            ->map(fn ($invoices, $period) => [
                'period' => $period,
                'label' => $period,
                'amount' => (float) collect($invoices)->sum(fn (StudentInvoice $invoice) => $invoice->balance_due),
                'count' => count($invoices),
            ])
            ->sortKeys()
            ->values();

        $paymentsForMonth = StudentPayment::query()
            ->whereYear('payment_date', $selectedYear)
            ->whereMonth('payment_date', $selectedMonth)
            ->sum('amount');

        $paymentsForYear = StudentPayment::query()
            ->whereYear('payment_date', $selectedYear)
            ->sum('amount');

        $currentPeriodKey = $viewMode === 'yearly'
            ? (string) $selectedYear
            : sprintf('%04d-%02d', $selectedYear, $selectedMonth);
        $currentPeriodLabel = $viewMode === 'yearly'
            ? (string) $selectedYear
            : Carbon::create($selectedYear, $selectedMonth, 1)->format('F Y');
        $expectedForPeriod = $viewMode === 'yearly'
            ? (float) (($expectedByYear->firstWhere('period', $currentPeriodKey)['amount'] ?? 0))
            : (float) (($expectedByMonth->firstWhere('period', $currentPeriodKey)['amount'] ?? 0));
        $collectedForPeriod = $viewMode === 'yearly'
            ? (float) $paymentsForYear
            : (float) $paymentsForMonth;
        $collectionGap = max(0, $expectedForPeriod - $collectedForPeriod);
        $collectionRate = $expectedForPeriod > 0
            ? round(($collectedForPeriod / $expectedForPeriod) * 100, 1)
            : 0.0;

        return view('reports.receivables', [
            'viewMode' => $viewMode,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'dueInvoices' => $dueInvoices,
            'expectedByMonth' => $expectedByMonth,
            'expectedByYear' => $expectedByYear,
            'totalOutstanding' => (float) $openInvoices->sum(fn (StudentInvoice $invoice) => $invoice->balance_due),
            'periodOutstanding' => (float) $dueInvoices->sum(fn (StudentInvoice $invoice) => $invoice->balance_due),
            'paymentsForMonth' => (float) $paymentsForMonth,
            'paymentsForYear' => (float) $paymentsForYear,
            'overdueInvoices' => $overdueInvoices,
            'overdueAmount' => (float) $overdueInvoices->sum(fn (StudentInvoice $invoice) => $invoice->balance_due),
            'currentPeriodLabel' => $currentPeriodLabel,
            'expectedForPeriod' => $expectedForPeriod,
            'collectedForPeriod' => $collectedForPeriod,
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
}
