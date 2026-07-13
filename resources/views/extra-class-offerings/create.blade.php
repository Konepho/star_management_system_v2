<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">Create Extra Class Offering</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('extra-class-offerings._form', [
                        'offering' => $offering,
                        'catalogs' => $catalogs,
                        'academicYears' => $academicYears,
                        'feeCategories' => $feeCategories,
                        'action' => route('extra-class-offerings.store'),
                        'submitLabel' => 'Create Offering',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
