<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePosSaleRequest;
use App\Models\PosProduct;
use App\Models\PosSale;
use App\Models\Wallet;
use App\Services\AuditLogService;
use App\Services\PosOwnerLookupService;
use App\Services\WalletLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PosSaleController extends Controller
{
    public function __construct(
        protected PosOwnerLookupService $ownerLookupService,
        protected WalletLedgerService $walletLedgerService,
    ) {
    }

    public function index(): View
    {
        return view('pos-sales.index', [
            'sales' => PosSale::query()
                ->with(['owner', 'performedBy'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $identifier = trim((string) $request->string('identifier'));
        $owner = $this->ownerLookupService->findOwnerByIdentifier($identifier);
        $wallet = $owner ? $this->ownerLookupService->walletForOwner($owner)->load('owner') : null;

        return view('pos-sales.create', [
            'identifier' => $identifier,
            'owner' => $owner,
            'wallet' => $wallet,
            'products' => PosProduct::query()->with('category')->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(StorePosSaleRequest $request): RedirectResponse
    {
        $wallet = Wallet::query()->with('owner')->findOrFail($request->validated('wallet_id'));
        $this->ownerLookupService->ensureWalletIsTransactable($wallet);

        $productIds = collect($request->validated('product_ids'));
        $quantities = collect($request->validated('quantities'));
        $products = PosProduct::query()
            ->where('status', 'active')
            ->whereIn('id', $productIds->all())
            ->get()
            ->keyBy('id');

        $lines = $productIds
            ->map(function (mixed $productId, int $index) use ($products, $quantities): ?array {
                $product = $products->get((int) $productId);
                $quantity = (int) ($quantities[$index] ?? 0);

                if (! $product || $quantity <= 0) {
                    return null;
                }

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                ];
            })
            ->filter()
            ->values();

        $sale = $this->walletLedgerService->createSale($wallet, new Collection($lines->all()), $request->validated('notes'));

        app(AuditLogService::class)->log(
            'finance',
            'pos_sales',
            'created',
            $sale,
            [],
            app(AuditLogService::class)->modelState($sale),
            'Created POS sale ' . $sale->sale_no . '.',
            [
                'wallet_id' => $wallet->id,
                'owner_type' => $wallet->ownerTypeLabel(),
                'owner_identifier' => $wallet->ownerIdentifier(),
                'item_count' => $sale->items->count(),
            ],
        );

        return redirect()
            ->route('pos-sales.show', $sale)
            ->with('status', 'POS sale recorded successfully.');
    }

    public function show(PosSale $posSale): View
    {
        return view('pos-sales.show', [
            'sale' => $posSale->load(['wallet.owner', 'owner', 'items.product', 'performedBy']),
        ]);
    }

    public function destroy(PosSale $posSale): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($posSale);
        $wasAlreadyReversed = $posSale->isReversed();

        if (! $wasAlreadyReversed) {
            $this->walletLedgerService->reverseSale($posSale);

            $auditLogService->log(
                'finance',
                'pos_sales',
                'reversed',
                $posSale->fresh(),
                $beforeState,
                $auditLogService->modelState($posSale->fresh()),
                'Reversed POS sale ' . $posSale->sale_no . '.',
                [
                    'wallet_id' => $posSale->wallet_id,
                ],
            );
        }

        return redirect()
            ->route('pos-sales.index')
            ->with('status', $wasAlreadyReversed ? 'POS sale already reversed.' : 'POS sale reversed successfully.');
    }
}
