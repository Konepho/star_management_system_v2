<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">New POS Sale</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-6">
                    <form method="GET" action="{{ route('pos-sales.create') }}" class="flex flex-col gap-3 sm:flex-row">
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
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="text-sm font-semibold uppercase tracking-wide text-slate-500">Wallet Owner</div>
                        <div class="mt-3 text-lg font-semibold text-slate-900">{{ $wallet->ownerName() }}</div>
                        <div class="mt-1 text-sm text-slate-600">{{ $wallet->ownerTypeLabel() }} · {{ $wallet->ownerIdentifier() }}</div>
                        <div class="mt-4 text-sm text-slate-500">Available balance</div>
                        <div class="text-2xl font-bold text-slate-900">{{ number_format((float) $wallet->current_balance, 2) }}</div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white shadow-sm lg:col-span-2">
                        <div class="p-6">
                            <form method="POST" action="{{ route('pos-sales.store') }}" class="space-y-6" x-data="posSaleForm()">
                                @csrf
                                <input type="hidden" name="wallet_id" value="{{ $wallet->id }}">

                                <div id="pos-sale-lines" class="space-y-4">
                                    <template x-for="(line, index) in lines" :key="index">
                                        <div class="grid gap-4 rounded-lg border border-slate-200 p-4 md:grid-cols-[1fr_140px_auto]">
                                            <div>
                                                <label class="text-sm font-medium text-slate-700">Product</label>
                                                <select :name="`product_ids[${index}]`" x-model="line.productId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="">Select product</option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-stock="{{ $product->stock_quantity }}">
                                                            {{ $product->category?->name ? $product->category->name . ' · ' : '' }}{{ $product->name }} · {{ number_format((float) $product->price, 2) }} · Stock {{ $product->stock_quantity }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="text-sm font-medium text-slate-700">Quantity</label>
                                                <input :name="`quantities[${index}]`" x-model="line.quantity" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                            <div class="flex items-end">
                                                <button type="button" @click="removeLine(index)" class="rounded-md border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Remove</button>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="flex items-center gap-4">
                                    <button type="button" @click="addLine()" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Add Product</button>
                                </div>

                                <div>
                                    <x-input-label for="notes" :value="__('Notes')" />
                                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('items')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('wallet_id')" />
                                </div>

                                <div class="flex items-center gap-4">
                                    <x-primary-button>Record Sale</x-primary-button>
                                    <a href="{{ route('wallets.show', $wallet) }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Back to Wallet</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function posSaleForm() {
            return {
                lines: [{ productId: '', quantity: 1 }],
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
