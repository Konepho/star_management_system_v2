@php
    $initialProductIds = old('product_ids', ['']);
    $initialQuantities = old('quantities', [1]);
    $initialLines = collect($initialProductIds)
        ->values()
        ->map(function ($productId, $index) use ($initialQuantities) {
            return [
                'productId' => (string) ($productId ?? ''),
                'quantity' => max(1, (int) ($initialQuantities[$index] ?? 1)),
            ];
        })
        ->filter(fn (array $line) => $line['productId'] !== '' || $line['quantity'] > 0)
        ->values();

    if ($initialLines->isEmpty()) {
        $initialLines = collect([[
            'productId' => '',
            'quantity' => 1,
        ]]);
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">POS Cashier</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-6">
                    <form method="GET" action="{{ route('pos-cashier.index') }}" class="flex flex-col gap-3 sm:flex-row">
                        <x-text-input name="identifier" type="text" class="block w-full" :value="$identifier" placeholder="Scan barcode / enter admission no or staff no" autofocus />
                        <x-primary-button>Find</x-primary-button>
                    </form>
                </div>
            </div>

            @if ($identifier !== '' && ! $wallet)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">No active student or staff was found for that identifier.</div>
            @endif

            @if ($wallet)
                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="text-sm font-semibold uppercase tracking-wide text-slate-500">Cashier Lookup</div>
                        <div class="mt-3 text-lg font-semibold text-slate-900">{{ $wallet->ownerName() }}</div>
                        <div class="mt-1 text-sm text-slate-600">{{ $wallet->ownerTypeLabel() }} · {{ $wallet->ownerIdentifier() }}</div>
                        <div class="mt-4 text-sm text-slate-500">Wallet balance</div>
                        <div class="text-3xl font-bold text-slate-900">{{ number_format((float) $wallet->current_balance, 2) }}</div>
                    </div>

                    <div class="space-y-6 lg:col-span-2">
                        @if (auth()->user()->hasPermission('pos_sales.create'))
                            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                                <div class="border-b border-slate-200 px-6 py-4">
                                    <h3 class="text-lg font-semibold text-slate-900">Quick Sale</h3>
                                </div>
                                <div class="p-6">
                                    @if ($errors->any())
                                        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                            <div class="font-semibold">Checkout could not be completed.</div>
                                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('pos-sales.store') }}" class="space-y-4" x-data='posCashierLines(@json($initialLines->all()))'>
                                        @csrf
                                        <input type="hidden" name="wallet_id" value="{{ $wallet->id }}">
                                        <template x-for="(line, index) in lines" :key="index">
                                            <div class="grid gap-4 rounded-lg border border-slate-200 p-4 md:grid-cols-[1fr_120px_auto]">
                                                <div>
                                                    <label class="text-sm font-medium text-slate-700">Product</label>
                                                    <select :name="`product_ids[${index}]`" x-model="line.productId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="">Select product</option>
                                                        @foreach ($products as $product)
                                                            <option value="{{ $product->id }}">{{ $product->category?->name ? $product->category->name . ' · ' : '' }}{{ $product->name }} · {{ number_format((float) $product->price, 2) }} · Stock {{ $product->stock_quantity }}</option>
                                                        @endforeach
                                                    </select>
                                                    <x-input-error class="mt-2" :messages="$errors->get('product_ids.*')" />
                                                </div>
                                                <div>
                                                    <label class="text-sm font-medium text-slate-700">Qty</label>
                                                    <input :name="`quantities[${index}]`" x-model="line.quantity" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <x-input-error class="mt-2" :messages="$errors->get('quantities.*')" />
                                                </div>
                                                <div class="flex items-end">
                                                    <button type="button" @click="removeLine(index)" class="rounded-md border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Remove</button>
                                                </div>
                                            </div>
                                        </template>

                                        <div>
                                            <label for="pos_cashier_notes" class="text-sm font-medium text-slate-700">Notes</label>
                                            <textarea id="pos_cashier_notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                                            <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                                            <x-input-error class="mt-2" :messages="$errors->get('items')" />
                                            <x-input-error class="mt-2" :messages="$errors->get('wallet_id')" />
                                        </div>

                                        <div class="flex items-center gap-4">
                                            <button type="button" @click="addLine()" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Add Product</button>
                                            <x-primary-button>Checkout</x-primary-button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function posCashierLines(initialLines = [{ productId: '', quantity: 1 }]) {
            return {
                lines: initialLines,
                addLine() {
                    this.lines.push({ productId: '', quantity: 1 });
                },
                removeLine(index) {
                    if (this.lines.length === 1) {
                        this.lines[0] = { productId: '', quantity: 1 };
                        return;
                    }
                    this.lines.splice(index, 1);
                },
            };
        }
    </script>
</x-app-layout>
