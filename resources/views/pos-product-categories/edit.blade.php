<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Edit POS Category</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('pos-product-categories.update', $category) }}">
                        @csrf
                        @method('PUT')
                        @include('pos-product-categories._form', ['submitLabel' => 'Update Category'])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
