<?php

namespace App\Http\Controllers;

use App\Models\PosProduct;
use App\Models\PosProductCategory;
use App\Services\PosOwnerLookupService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosCashierController extends Controller
{
    public function __construct(
        protected PosOwnerLookupService $ownerLookupService,
    ) {}

    public function __invoke(Request $request): View
    {
        $section = (string) ($request->route('section') ?: PosProductCategory::SECTION_SNACK_SHOP);
        $identifier = trim((string) $request->string('identifier'));
        $owner = $this->ownerLookupService->findOwnerByIdentifier($identifier);
        $wallet = $owner ? $this->ownerLookupService->walletForOwner($owner)->load('owner') : null;

        return view('pos-cashier.index', [
            'identifier' => $identifier,
            'owner' => $owner,
            'wallet' => $wallet,
            'section' => $section,
            'sectionLabel' => PosProductCategory::sectionOptions()[$section],
            'cashierRoute' => $section === PosProductCategory::SECTION_STATIONERY
                ? 'pos-cashier.stationery'
                : 'pos-cashier.snack-shop',
            'products' => PosProduct::query()
                ->with('category')
                ->where('status', 'active')
                ->whereHas('category', fn ($query) => $query
                    ->where('status', 'active')
                    ->where('pos_section', $section))
                ->orderBy('name')
                ->get(),
        ]);
    }
}
