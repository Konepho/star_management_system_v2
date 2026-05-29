<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use App\Services\AuditLogService;
use App\Services\WalletLedgerService;
use Illuminate\Http\RedirectResponse;

class WalletTransactionController extends Controller
{
    public function __construct(
        protected WalletLedgerService $walletLedgerService,
    ) {
    }

    public function destroy(WalletTransaction $walletTransaction): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($walletTransaction);
        $wallet = $walletTransaction->wallet;
        $wasAlreadyReversed = $walletTransaction->isReversed();

        if (! $wasAlreadyReversed) {
            $this->walletLedgerService->reverseTopup($walletTransaction);

            $auditLogService->log(
                'finance',
                'wallet_transactions',
                'reversed',
                $walletTransaction->fresh(),
                $beforeState,
                $auditLogService->modelState($walletTransaction->fresh()),
                'Reversed wallet transaction ' . ($walletTransaction->transaction_no ?: '#' . $walletTransaction->id) . '.',
                [
                    'wallet_id' => $wallet?->id,
                ],
            );
        }

        return redirect()
            ->route('wallets.show', $wallet)
            ->with('status', $wasAlreadyReversed ? 'Wallet transaction already reversed.' : 'Wallet top-up reversed successfully.');
    }
}
