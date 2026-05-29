<?php

namespace App\Http\Controllers;

use App\Models\PosProduct;
use App\Services\PosOwnerLookupService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosCashierController extends Controller
{
    public function __construct(
        protected PosOwnerLookupService $ownerLookupService,
    ) {
    }

    public function __invoke(Request $request): View
    {
        $identifier = trim((string) $request->string('identifier'));
        $owner = $this->ownerLookupService->findOwnerByIdentifier($identifier);
        $wallet = $owner ? $this->ownerLookupService->walletForOwner($owner)->load('owner') : null;

        return view('pos-cashier.index', [
            'identifier' => $identifier,
            'owner' => $owner,
            'wallet' => $wallet,
            'products' => PosProduct::query()->with('category')->where('status', 'active')->orderBy('name')->get(),
        ]);
    }
}
