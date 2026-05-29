<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Student;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));

        $wallets = Wallet::query()
            ->with(['owner'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $walletQuery) use ($search): void {
                    $walletQuery
                        ->where(function (Builder $studentQuery) use ($search): void {
                            $studentQuery->where('owner_type', Student::class)
                                ->whereIn('owner_id', Student::query()
                                    ->where('admission_no', 'like', '%' . $search . '%')
                                    ->orWhere('name_en', 'like', '%' . $search . '%')
                                    ->orWhere('preferred_name', 'like', '%' . $search . '%')
                                    ->pluck('id'));
                        })
                        ->orWhere(function (Builder $staffQuery) use ($search): void {
                            $staffQuery->where('owner_type', Staff::class)
                                ->whereIn('owner_id', Staff::query()
                                    ->where(function (Builder $query) use ($search): void {
                                        $query->where('staff_no', 'like', '%' . $search . '%')
                                            ->orWhere('email', 'like', '%' . $search . '%')
                                            ->orWhere(function (Builder $nameQuery) use ($search): void {
                                                $nameQuery->where('first_name', 'like', '%' . $search . '%')
                                                    ->orWhere('last_name', 'like', '%' . $search . '%');
                                            });
                                    })
                                    ->where(function (Builder $statusQuery): void {
                                        $statusQuery->where('status', 'active')
                                            ->orWhere('status', 'on-leave');
                                    })
                                    ->pluck('id'));
                        });
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return view('wallets.index', [
            'wallets' => $wallets,
            'search' => $search,
        ]);
    }

    public function show(Wallet $wallet): View
    {
        return view('wallets.show', [
            'wallet' => $wallet->load([
                'owner',
                'transactions.performedBy',
                'transactions.reference',
                'posSales.items',
            ]),
        ]);
    }
}
