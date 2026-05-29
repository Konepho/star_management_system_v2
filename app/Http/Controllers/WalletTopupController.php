<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWalletTopupRequest;
use App\Models\Wallet;
use App\Services\AuditLogService;
use App\Services\PosOwnerLookupService;
use App\Services\WalletLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletTopupController extends Controller
{
    public function __construct(
        protected PosOwnerLookupService $ownerLookupService,
        protected WalletLedgerService $walletLedgerService,
    ) {
    }

    public function create(Request $request): View
    {
        $identifier = trim((string) $request->string('identifier'));
        $owner = $this->ownerLookupService->findOwnerByIdentifier($identifier);
        $wallet = $owner ? $this->ownerLookupService->walletForOwner($owner)->load('owner') : null;

        return view('wallet-topups.create', [
            'identifier' => $identifier,
            'owner' => $owner,
            'wallet' => $wallet,
        ]);
    }

    public function store(StoreWalletTopupRequest $request): RedirectResponse
    {
        $wallet = Wallet::query()->with('owner')->findOrFail($request->validated('wallet_id'));
        $this->ownerLookupService->ensureWalletIsTransactable($wallet);

        $transaction = $this->walletLedgerService->topup(
            $wallet,
            (float) $request->validated('amount'),
            (string) $request->validated('payment_method'),
            $request->validated('notes'),
        );

        app(AuditLogService::class)->log(
            'finance',
            'wallet_transactions',
            'topup',
            $transaction,
            [],
            app(AuditLogService::class)->modelState($transaction),
            'Topped up wallet for ' . $wallet->ownerName() . '.',
            [
                'wallet_id' => $wallet->id,
                'owner_type' => $wallet->ownerTypeLabel(),
                'owner_identifier' => $wallet->ownerIdentifier(),
            ],
        );

        return redirect()
            ->route('wallets.show', $wallet)
            ->with('status', 'Wallet top-up recorded successfully.');
    }
}
