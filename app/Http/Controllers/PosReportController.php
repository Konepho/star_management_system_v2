<?php

namespace App\Http\Controllers;

use App\Models\PosSale;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $dateFrom = $request->string('date_from')->toString() ?: now()->startOfMonth()->toDateString();
        $dateTo = $request->string('date_to')->toString() ?: now()->toDateString();

        $salesQuery = PosSale::query()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        $topupsQuery = WalletTransaction::query()
            ->where('transaction_type', WalletTransaction::TYPE_TOPUP)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        $salesForSummary = (clone $salesQuery)
            ->with('performedBy')
            ->get();

        $topupsForSummary = (clone $topupsQuery)
            ->with('performedBy')
            ->get();

        return view('pos-reports.index', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'salesTotal' => (float) (clone $salesQuery)->where('status', PosSale::STATUS_POSTED)->sum('total_amount'),
            'salesReversedTotal' => (float) (clone $salesQuery)->where('status', PosSale::STATUS_REVERSED)->sum('total_amount'),
            'topupTotal' => (float) (clone $topupsQuery)->where('status', WalletTransaction::STATUS_POSTED)->sum('amount'),
            'topupReversedTotal' => (float) (clone $topupsQuery)->where('status', WalletTransaction::STATUS_REVERSED)->sum('amount'),
            'cashierClosings' => $this->buildCashierClosings($salesForSummary, $topupsForSummary),
            'recentSales' => (clone $salesQuery)->with('owner')->orderByDesc('created_at')->limit(20)->get(),
            'recentTopups' => (clone $topupsQuery)->with('wallet.owner', 'performedBy')->orderByDesc('created_at')->limit(20)->get(),
        ]);
    }

    private function buildCashierClosings(Collection $sales, Collection $topups): Collection
    {
        $salesRows = $sales->map(fn (PosSale $sale): array => [
            'date' => $sale->created_at->toDateString(),
            'cashier_id' => $sale->performed_by_user_id,
            'cashier_name' => $sale->performedBy?->name ?? 'Unknown User',
            'sale_count' => $sale->status === PosSale::STATUS_POSTED ? 1 : 0,
            'posted_sale_total' => $sale->status === PosSale::STATUS_POSTED ? (float) $sale->total_amount : 0.0,
            'reversed_sale_total' => $sale->status === PosSale::STATUS_REVERSED ? (float) $sale->total_amount : 0.0,
            'topup_count' => 0,
            'posted_topup_total' => 0.0,
            'reversed_topup_total' => 0.0,
        ]);

        $topupRows = $topups->map(fn (WalletTransaction $topup): array => [
            'date' => $topup->created_at->toDateString(),
            'cashier_id' => $topup->performed_by_user_id,
            'cashier_name' => $topup->performedBy?->name ?? 'Unknown User',
            'sale_count' => 0,
            'posted_sale_total' => 0.0,
            'reversed_sale_total' => 0.0,
            'topup_count' => $topup->status === WalletTransaction::STATUS_POSTED ? 1 : 0,
            'posted_topup_total' => $topup->status === WalletTransaction::STATUS_POSTED ? (float) $topup->amount : 0.0,
            'reversed_topup_total' => $topup->status === WalletTransaction::STATUS_REVERSED ? (float) $topup->amount : 0.0,
        ]);

        return $salesRows
            ->concat($topupRows)
            ->groupBy(fn (array $row): string => $row['date'].'|'.$row['cashier_id'])
            ->map(function (Collection $rows): array {
                $first = $rows->first();

                return [
                    'date' => $first['date'],
                    'cashier_name' => $first['cashier_name'],
                    'sale_count' => $rows->sum('sale_count'),
                    'topup_count' => $rows->sum('topup_count'),
                    'posted_sale_total' => $rows->sum('posted_sale_total'),
                    'reversed_sale_total' => $rows->sum('reversed_sale_total'),
                    'posted_topup_total' => $rows->sum('posted_topup_total'),
                    'reversed_topup_total' => $rows->sum('reversed_topup_total'),
                ];
            })
            ->sortBy([
                ['date', 'desc'],
                ['cashier_name', 'asc'],
            ])
            ->values();
    }
}
