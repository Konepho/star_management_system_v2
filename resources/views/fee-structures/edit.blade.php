<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ __('Edit Fee Structure') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('fee-structures._form', [
                        'feeStructure' => $feeStructure,
                        'academicYears' => $academicYears,
                        'grades' => $grades,
                        'feeCategories' => $feeCategories,
                        'action' => route('fee-structures.update', $feeStructure),
                        'method' => 'PATCH',
                        'submitLabel' => 'Update Fee Structure',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
