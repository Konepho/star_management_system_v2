<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">Wallets</h2>
                <p class="mt-1 text-sm text-slate-700">View prepaid credit balances for students and staff.</p>
            </div>
            <a href="{{ route('wallet-topups.create') }}" class="inline-flex w-full items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600 sm:w-auto">
                Wallet Top-up
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('wallets.index') }}" class="flex flex-col gap-3 sm:flex-row">
                    <x-text-input name="search" type="text" class="block w-full" :value="$search" placeholder="Search admission no, staff no, or name" />
                    <x-primary-button>Search</x-primary-button>
                    @if ($search !== '')
                        <a href="{{ route('wallets.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</a>
                    @endif
                </form>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Owner</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Identifier</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Balance</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($wallets as $wallet)
                                    <tr>
                                        <td class="px-4 py-4 font-medium text-slate-900">{{ $wallet->ownerName() }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $wallet->ownerTypeLabel() }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $wallet->ownerIdentifier() }}</td>
                                        <td class="px-4 py-4 text-sm font-medium text-slate-900">{{ number_format((float) $wallet->current_balance, 2) }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ ucfirst($wallet->status) }}</td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="{{ route('wallets.show', $wallet) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">View</a>
                                                @if (auth()->user()->hasPermission('wallets.topup'))
                                                    <a href="{{ route('wallet-topups.create', ['identifier' => $wallet->ownerIdentifier()]) }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-600">Top-up</a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No wallets found yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
