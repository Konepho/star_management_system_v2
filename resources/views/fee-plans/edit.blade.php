<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Fee Plan') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('fee-plans._form', [
                        'feePlan' => $feePlan,
                        'academicYears' => $academicYears,
                        'feeStructures' => $feeStructures,
                        'action' => route('fee-plans.update', $feePlan),
                        'method' => 'PATCH',
                        'submitLabel' => 'Update Fee Plan',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
