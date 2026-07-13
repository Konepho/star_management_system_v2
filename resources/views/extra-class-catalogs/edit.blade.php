<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">Edit Extra Class Catalog</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('extra-class-catalogs._form', [
                        'catalog' => $catalog,
                        'action' => route('extra-class-catalogs.update', $catalog),
                        'method' => 'PUT',
                        'submitLabel' => 'Update Extra Class',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
