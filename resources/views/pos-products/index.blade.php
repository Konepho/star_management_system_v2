<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">POS Products</h2>
                <p class="mt-1 text-sm text-slate-700">Manage prepaid-credit shop items, prices, and stock levels.</p>
            </div>
            <a href="{{ route('pos-products.create') }}" class="inline-flex w-full items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600 sm:w-auto">
                Add POS Product
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Product</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">SKU</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Price</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Stock</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($products as $product)
                                    <tr>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $product->category?->name ?: '—' }}</td>
                                        <td class="px-4 py-4">
                                            <div class="font-medium text-slate-900">{{ $product->name }}</div>
                                            @if ($product->description)
                                                <div class="text-xs text-slate-500">{{ $product->description }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $product->sku ?: '—' }}</td>
                                        <td class="px-4 py-4 text-sm font-medium text-slate-900">{{ number_format((float) $product->price, 2) }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ number_format((int) $product->stock_quantity) }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $product->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                                {{ ucfirst($product->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="{{ route('pos-products.edit', $product) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Edit</a>
                                                <form method="POST" action="{{ route('pos-products.destroy', $product) }}" onsubmit="return confirm('Deactivate this POS product?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">Deactivate</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No POS products yet.</td>
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
