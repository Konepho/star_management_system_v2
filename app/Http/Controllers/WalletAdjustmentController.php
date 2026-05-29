<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWalletAdjustmentRequest;
use App\Models\Wallet;
use App\Services\AuditLogService;
use App\Services\PosOwnerLookupService;
use App\Services\WalletLedgerService;
use Illuminate\Http\RedirectResponse;

class WalletAdjustmentController extends Controller
{
    public function __construct(
        protected PosOwnerLookupService $ownerLookupService,
        protected WalletLedgerService $walletLedgerService,
    ) {
    }

    public function store(StoreWalletAdjustmentRequest $request): RedirectResponse
    {
        $wallet = Wallet::query()->with('owner')->findOrFail($request->validated('wallet_id'));
        $this->ownerLookupService->ensureWalletIsTransactable($wallet);

        $transaction = $this->walletLedgerService->adjust(
            $wallet,
            (float) $request->validated('amount_delta'),
            (string) $request->validated('reason'),
            $request->validated('notes'),
        );

        app(AuditLogService::class)->log(
            'finance',
            'wallet_transactions',
            'adjusted',
            $transaction,
            [],
            app(AuditLogService::class)->modelState($transaction),
            'Adjusted wallet for ' . $wallet->ownerName() . '.',
            [
                'wallet_id' => $wallet->id,
                'owner_identifier' => $wallet->ownerIdentifier(),
            ],
        );

        return redirect()
            ->route('wallets.show', $wallet)
            ->with('status', 'Wallet adjusted successfully.');
    }
}
