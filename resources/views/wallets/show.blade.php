<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ $wallet->ownerName() }} Wallet</h2>
                <p class="mt-1 text-sm text-slate-700">{{ $wallet->ownerTypeLabel() }} · {{ $wallet->ownerIdentifier() }}</p>
            </div>
            <div class="flex gap-3">
                @if (auth()->user()->hasPermission('wallets.topup'))
                    <a href="{{ route('wallet-topups.create', ['identifier' => $wallet->ownerIdentifier()]) }}" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                        Top-up
                    </a>
                @endif
                @if (auth()->user()->hasPermission('pos_sales.create'))
                    <a href="{{ route('pos-sales.create', ['identifier' => $wallet->ownerIdentifier()]) }}" class="inline-flex items-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600">
                        New Sale
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="text-sm font-semibold uppercase tracking-wide text-slate-500">Current Balance</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">{{ number_format((float) $wallet->current_balance, 2) }}</div>
                    <div class="mt-3 text-sm text-slate-600">Status: {{ ucfirst($wallet->status) }}</div>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm lg:col-span-2">
                    <div class="text-sm font-semibold uppercase tracking-wide text-slate-500">Owner Details</div>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Name</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ $wallet->ownerName() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Identifier</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ $wallet->ownerIdentifier() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            @if (auth()->user()->hasPermission('wallets.adjust'))
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Manual Adjustment</h3>
                    </div>
                    <div class="p-6">
                        <form method="POST" action="{{ route('wallet-adjustments.store') }}" class="grid gap-6 md:grid-cols-3">
                            @csrf
                            <input type="hidden" name="wallet_id" value="{{ $wallet->id }}">
                            <div>
                                <x-input-label for="amount_delta" :value="__('Amount Change')" />
                                <x-text-input id="amount_delta" name="amount_delta" type="number" step="0.01" class="mt-1 block w-full" placeholder="Use negative for deduction" />
                                <x-input-error class="mt-2" :messages="$errors->get('amount_delta')" />
                            </div>
                            <div>
                                <x-input-label for="reason" :value="__('Reason')" />
                                <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full" />
                                <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                            </div>
                            <div>
                                <x-input-label for="adjustment_notes" :value="__('Notes')" />
                                <x-text-input id="adjustment_notes" name="notes" type="text" class="mt-1 block w-full" />
                                <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                            </div>
                            <div class="md:col-span-3">
                                <x-primary-button>Apply Adjustment</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Wallet Transactions</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @forelse ($wallet->transactions as $transaction)
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <div class="font-medium text-slate-900">{{ strtoupper(str_replace('_', ' ', $transaction->transaction_type)) }}</div>
                                            <div class="text-xs text-slate-500">{{ $transaction->transaction_no ?: 'Internal entry' }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-semibold text-slate-900">{{ number_format((float) $transaction->amount, 2) }}</div>
                                            <div class="text-xs text-slate-500">{{ $transaction->created_at?->format('Y-m-d H:i') }}</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-sm text-slate-600">
                                        Balance: {{ number_format((float) $transaction->balance_before, 2) }} → {{ number_format((float) $transaction->balance_after, 2) }}
                                    </div>
                                    @if ($transaction->notes)
                                        <div class="mt-2 text-sm text-slate-600">{{ $transaction->notes }}</div>
                                    @endif
                                    @if (auth()->user()->hasPermission('wallets.adjust') && $transaction->transaction_type === \App\Models\WalletTransaction::TYPE_TOPUP && ! $transaction->isReversed())
                                        <form method="POST" action="{{ route('wallet-transactions.destroy', $transaction) }}" class="mt-3" onsubmit="return confirm('Reverse this top-up?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">Reverse Top-up</button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <div class="text-sm text-slate-500">No wallet transactions yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">POS Sales</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @forelse ($wallet->posSales as $sale)
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <a href="{{ route('pos-sales.show', $sale) }}" class="font-medium text-sky-700 hover:text-sky-600">{{ $sale->sale_no }}</a>
                                            <div class="text-xs text-slate-500">{{ $sale->created_at?->format('Y-m-d H:i') }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-semibold text-slate-900">{{ number_format((float) $sale->total_amount, 2) }}</div>
                                            <div class="text-xs text-slate-500">{{ ucfirst($sale->status) }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-slate-500">No POS sales yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
