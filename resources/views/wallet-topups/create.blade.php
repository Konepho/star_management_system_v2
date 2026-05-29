<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Wallet Top-up</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-6">
                    <form method="GET" action="{{ route('wallet-topups.create') }}" class="flex flex-col gap-3 sm:flex-row">
                        <x-text-input name="identifier" type="text" class="block w-full" :value="$identifier" placeholder="Scan or enter student admission no / staff no" />
                        <x-primary-button>Find</x-primary-button>
                    </form>
                </div>
            </div>

            @if ($identifier !== '' && ! $wallet)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">No active student or staff was found for that identifier.</div>
            @endif

            @if ($wallet)
                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="rounded-lg bg-white p-6 shadow-sm lg:col-span-1">
                        <div class="text-sm font-semibold uppercase tracking-wide text-slate-500">Wallet Owner</div>
                        <div class="mt-3 text-lg font-semibold text-slate-900">{{ $wallet->ownerName() }}</div>
                        <div class="mt-1 text-sm text-slate-600">{{ $wallet->ownerTypeLabel() }} · {{ $wallet->ownerIdentifier() }}</div>
                        <div class="mt-4 text-sm text-slate-500">Current balance</div>
                        <div class="text-2xl font-bold text-slate-900">{{ number_format((float) $wallet->current_balance, 2) }}</div>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-white shadow-sm lg:col-span-2">
                        <div class="p-6">
                            <form method="POST" action="{{ route('wallet-topups.store') }}" class="space-y-6">
                                @csrf
                                <input type="hidden" name="wallet_id" value="{{ $wallet->id }}">
                                <div class="grid gap-6 md:grid-cols-2">
                                    <div>
                                        <x-input-label for="amount" :value="__('Top-up Amount')" />
                                        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount')" required />
                                        <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                                    </div>
                                    <div>
                                        <x-input-label for="payment_method" :value="__('Payment Method')" />
                                        <select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @php($selectedMethod = old('payment_method', 'cash'))
                                            <option value="cash" @selected($selectedMethod === 'cash')>Cash</option>
                                            <option value="mmqr" @selected($selectedMethod === 'mmqr')>MMQR</option>
                                            <option value="kbzpay" @selected($selectedMethod === 'kbzpay')>KBZPay</option>
                                        </select>
                                        <x-input-error class="mt-2" :messages="$errors->get('payment_method')" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <x-input-label for="notes" :value="__('Notes')" />
                                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <x-primary-button>Record Top-up</x-primary-button>
                                    <a href="{{ route('wallets.show', $wallet) }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Back to Wallet</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
