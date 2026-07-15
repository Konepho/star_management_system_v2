@php
    $initialProductIds = old('product_ids', []);
    $initialQuantities = old('quantities', []);
    $initialLines = collect($initialProductIds)
        ->values()
        ->map(function ($productId, $index) use ($initialQuantities) {
            return [
                'productId' => (string) ($productId ?? ''),
                'quantity' => max(1, (int) ($initialQuantities[$index] ?? 1)),
            ];
        })
        ->filter(fn (array $line) => $line['productId'] !== '')
        ->values();

    $productCatalog = $products->map(fn ($product) => [
        'id' => (string) $product->id,
        'name' => $product->name,
        'sku' => $product->sku ?? '',
        'categoryId' => (string) ($product->pos_product_category_id ?? ''),
        'categoryName' => $product->category?->name ?? 'Uncategorized',
        'price' => (float) $product->price,
        'stock' => (int) $product->stock_quantity,
    ])->values();

    $categories = $products
        ->pluck('category')
        ->filter()
        ->unique('id')
        ->sortBy('name')
        ->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ $sectionLabel }} POS</h2>
                <p class="mt-1 text-sm text-slate-700">Tap a product card to add it to the cart.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('pos-cashier.snack-shop', ['identifier' => $identifier]) }}" class="rounded-md px-3 py-2 text-sm font-semibold {{ $section === 'snack_shop' ? 'bg-sky-700 text-white' : 'border border-slate-300 text-slate-700' }}">Snack Shop</a>
                <a href="{{ route('pos-cashier.stationery', ['identifier' => $identifier]) }}" class="rounded-md px-3 py-2 text-sm font-semibold {{ $section === 'stationery' ? 'bg-sky-700 text-white' : 'border border-slate-300 text-slate-700' }}">Stationery</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="p-4 sm:p-5">
                    <form method="GET" action="{{ route($cashierRoute) }}" class="flex flex-col gap-3 sm:flex-row">
                        <x-text-input name="identifier" type="text" class="block w-full text-base" :value="$identifier" placeholder="Scan barcode / enter admission no or staff no" autofocus />
                        <x-primary-button class="justify-center sm:min-w-28">Find Wallet</x-primary-button>
                    </form>
                </div>
            </div>

            @if ($identifier !== '' && ! $wallet)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">No active student or staff was found for that identifier.</div>
            @endif

            @if ($wallet)
                <div class="rounded-xl border border-sky-100 bg-sky-50 px-4 py-4 shadow-sm sm:flex sm:items-center sm:justify-between sm:px-5">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-sky-700">Customer</div>
                        <div class="mt-1 text-lg font-bold text-slate-900">{{ $wallet->ownerName() }}</div>
                        <div class="text-sm text-slate-600">{{ $wallet->ownerTypeLabel() }} · {{ $wallet->ownerIdentifier() }}</div>
                    </div>
                    <div class="mt-3 sm:mt-0 sm:text-right">
                        <div class="text-xs font-semibold uppercase tracking-wide text-sky-700">Wallet Balance</div>
                        <div class="mt-1 text-2xl font-bold text-slate-900">{{ number_format((float) $wallet->current_balance, 2) }}</div>
                    </div>
                </div>

                @if (auth()->user()->hasPermission('pos_sales.create'))
                    <form
                        method="POST"
                        action="{{ route('pos-sales.store') }}"
                        x-data='posCashierCart(@json($productCatalog), @json($initialLines), {{ (float) $wallet->current_balance }})'
                        class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]"
                    >
                        @csrf
                        <input type="hidden" name="wallet_id" value="{{ $wallet->id }}">
                        <input type="hidden" name="pos_section" value="{{ $section }}">

                        <section class="min-w-0 overflow-hidden rounded-xl bg-white shadow-sm">
                            <div class="border-b border-slate-200 p-4 sm:p-5">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-slate-900">Products</h3>
                                        <p class="text-sm text-slate-500">Search or choose a category, then tap an item.</p>
                                    </div>
                                    <div class="relative w-full sm:max-w-xs">
                                        <input x-model="search" type="search" class="block w-full rounded-lg border-slate-300 py-2.5 pl-3 pr-3 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" placeholder="Search name, SKU, or category">
                                    </div>
                                </div>

                                <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
                                    <button type="button" @click="selectedCategory = 'all'" :class="selectedCategory === 'all' ? 'bg-sky-700 text-white border-sky-700' : 'bg-white text-slate-700 border-slate-300'" class="shrink-0 rounded-full border px-4 py-2 text-sm font-semibold">All</button>
                                    @foreach ($categories as $category)
                                        <button type="button" @click="selectedCategory = '{{ $category->id }}'" :class="selectedCategory === '{{ $category->id }}' ? 'bg-sky-700 text-white border-sky-700' : 'bg-white text-slate-700 border-slate-300'" class="shrink-0 rounded-full border px-4 py-2 text-sm font-semibold">{{ $category->name }}</button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="p-4 sm:p-5">
                                @if ($products->isEmpty())
                                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-8 text-center text-sm text-amber-700">
                                        No active products are assigned to the {{ $sectionLabel }} page. Assign a category to this cashier page, then add products to that category.
                                    </div>
                                @else
                                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                                        @foreach ($products as $product)
                                            <button
                                                type="button"
                                                x-show="matchesProduct('{{ $product->id }}')"
                                                @click="addProduct('{{ $product->id }}')"
                                                :class="quantityFor('{{ $product->id }}') > 0 ? 'border-sky-500 bg-sky-50 ring-2 ring-sky-100' : 'border-slate-200 bg-white hover:border-sky-300 hover:bg-sky-50/50'"
                                                class="relative min-h-36 rounded-xl border p-4 text-left transition focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:opacity-60"
                                                @disabled($product->stock_quantity <= 0)
                                            >
                                                <span class="block text-xs font-semibold uppercase tracking-wide text-sky-700">{{ $product->category?->name ?? 'Product' }}</span>
                                                <span class="mt-2 block font-bold leading-tight text-slate-900">{{ $product->name }}</span>
                                                <span class="mt-2 block text-lg font-black text-slate-900">{{ number_format((float) $product->price, 2) }}</span>
                                                <span class="mt-2 block text-xs {{ $product->stock_quantity > 0 ? 'text-slate-500' : 'font-semibold text-rose-600' }}">
                                                    {{ $product->stock_quantity > 0 ? 'Stock ' . number_format((int) $product->stock_quantity) : 'Out of stock' }}
                                                </span>
                                                <span x-show="quantityFor('{{ $product->id }}') > 0" x-text="quantityFor('{{ $product->id }}')" class="absolute right-2 top-2 flex h-7 min-w-7 items-center justify-center rounded-full bg-sky-700 px-2 text-xs font-bold text-white"></span>
                                            </button>
                                        @endforeach
                                    </div>

                                    <div x-show="visibleProductCount === 0" x-cloak class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                        No products match this search.
                                    </div>
                                @endif
                            </div>
                        </section>

                        <aside class="overflow-hidden rounded-xl bg-white shadow-sm lg:sticky lg:top-5">
                            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Shopping Cart</h3>
                                    <p class="text-sm text-slate-500" x-text="`${cartItemCount} item${cartItemCount === 1 ? '' : 's'}`"></p>
                                </div>
                                <button x-show="lines.length > 0" type="button" @click="clearCart()" class="text-sm font-semibold text-rose-600 hover:text-rose-700">Clear</button>
                            </div>

                            @if ($errors->any())
                                <div class="m-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                    <div class="font-semibold">Checkout could not be completed.</div>
                                    <ul class="mt-2 list-disc space-y-1 pl-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="max-h-[38vh] overflow-y-auto px-4 py-2 lg:max-h-[46vh]">
                                <div x-show="lines.length === 0" class="py-10 text-center">
                                    <div class="text-sm font-medium text-slate-600">The cart is empty.</div>
                                    <div class="mt-1 text-xs text-slate-400">Tap a product card to begin.</div>
                                </div>

                                <template x-for="(line, index) in lines" :key="line.productId">
                                    <div class="border-b border-slate-100 py-4 last:border-0">
                                        <input type="hidden" :name="`product_ids[${index}]`" :value="line.productId">
                                        <input type="hidden" :name="`quantities[${index}]`" :value="line.quantity">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-bold text-slate-900" x-text="productFor(line.productId).name"></div>
                                                <div class="mt-1 text-xs text-slate-500" x-text="`${productFor(line.productId).categoryName} · ${money(productFor(line.productId).price)}`"></div>
                                            </div>
                                            <button type="button" @click="removeProduct(line.productId)" class="text-xs font-semibold text-rose-600">Remove</button>
                                        </div>
                                        <div class="mt-3 flex items-center justify-between">
                                            <div class="inline-flex items-center rounded-lg border border-slate-300">
                                                <button type="button" @click="decrement(line.productId)" class="h-9 w-9 text-lg font-bold text-slate-700 hover:bg-slate-100" aria-label="Decrease quantity">−</button>
                                                <span class="min-w-9 text-center text-sm font-bold text-slate-900" x-text="line.quantity"></span>
                                                <button type="button" @click="increment(line.productId)" :disabled="line.quantity >= productFor(line.productId).stock" class="h-9 w-9 text-lg font-bold text-slate-700 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-30" aria-label="Increase quantity">+</button>
                                            </div>
                                            <div class="text-sm font-bold text-slate-900" x-text="money(productFor(line.productId).price * line.quantity)"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="border-t border-slate-200 bg-slate-50 p-5">
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between text-slate-600"><span>Wallet balance</span><span x-text="money(walletBalance)"></span></div>
                                    <div class="flex justify-between text-lg font-black text-slate-900"><span>Total</span><span x-text="money(cartTotal)"></span></div>
                                    <div class="flex justify-between font-semibold" :class="balanceAfter < 0 ? 'text-rose-700' : 'text-emerald-700'"><span>Balance after</span><span x-text="money(balanceAfter)"></span></div>
                                </div>

                                <div x-show="balanceAfter < 0" x-cloak class="mt-3 rounded-lg bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700">Wallet balance is not enough for this sale.</div>

                                <div class="mt-4">
                                    <label for="pos_cashier_notes" class="text-sm font-medium text-slate-700">Notes <span class="font-normal text-slate-400">(optional)</span></label>
                                    <textarea id="pos_cashier_notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('notes') }}</textarea>
                                </div>

                                <button type="submit" :disabled="lines.length === 0 || balanceAfter < 0" class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-sky-700 px-4 py-3 text-base font-bold text-white shadow-sm hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300">
                                    Complete Checkout
                                </button>
                            </div>
                        </aside>
                    </form>
                @endif
            @endif
        </div>
    </div>

    <script>
        function posCashierCart(catalog, initialLines, walletBalance) {
            const products = catalog.map((product) => ({
                ...product,
                id: String(product.id),
                categoryId: String(product.categoryId),
                price: Number(product.price),
                stock: Number(product.stock),
            }));
            const productsById = Object.fromEntries(products.map((product) => [product.id, product]));
            const lines = initialLines
                .map((line) => ({
                    productId: String(line.productId),
                    quantity: Math.max(1, Number(line.quantity) || 1),
                }))
                .filter((line) => productsById[line.productId])
                .map((line) => ({
                    ...line,
                    quantity: Math.min(line.quantity, productsById[line.productId].stock),
                }));

            return {
                products,
                productsById,
                lines,
                walletBalance: Number(walletBalance),
                search: '',
                selectedCategory: 'all',

                productFor(productId) {
                    return this.productsById[String(productId)];
                },

                quantityFor(productId) {
                    return this.lines.find((line) => line.productId === String(productId))?.quantity ?? 0;
                },

                addProduct(productId) {
                    const id = String(productId);
                    const product = this.productFor(id);
                    const line = this.lines.find((item) => item.productId === id);

                    if (! product || product.stock <= 0) {
                        return;
                    }

                    if (line) {
                        if (line.quantity < product.stock) {
                            line.quantity++;
                        }
                        return;
                    }

                    this.lines.push({ productId: id, quantity: 1 });
                },

                increment(productId) {
                    this.addProduct(productId);
                },

                decrement(productId) {
                    const id = String(productId);
                    const line = this.lines.find((item) => item.productId === id);

                    if (! line) {
                        return;
                    }

                    if (line.quantity <= 1) {
                        this.removeProduct(id);
                        return;
                    }

                    line.quantity--;
                },

                removeProduct(productId) {
                    const id = String(productId);
                    this.lines = this.lines.filter((line) => line.productId !== id);
                },

                clearCart() {
                    this.lines = [];
                },

                matchesProduct(productId) {
                    const product = this.productFor(productId);
                    const query = this.search.trim().toLowerCase();
                    const matchesCategory = this.selectedCategory === 'all' || product.categoryId === this.selectedCategory;
                    const matchesSearch = query === '' || [product.name, product.sku, product.categoryName]
                        .some((value) => String(value).toLowerCase().includes(query));

                    return matchesCategory && matchesSearch;
                },

                get visibleProductCount() {
                    return this.products.filter((product) => this.matchesProduct(product.id)).length;
                },

                get cartItemCount() {
                    return this.lines.reduce((total, line) => total + Number(line.quantity), 0);
                },

                get cartTotal() {
                    return this.lines.reduce((total, line) => total + (this.productFor(line.productId).price * line.quantity), 0);
                },

                get balanceAfter() {
                    return this.walletBalance - this.cartTotal;
                },

                money(value) {
                    return Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
            };
        }
    </script>
</x-app-layout>
