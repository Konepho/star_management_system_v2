<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Add POS Product</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('pos-products.store') }}">
                        @csrf
                        @include('pos-products._form', ['submitLabel' => 'Create Product'])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
