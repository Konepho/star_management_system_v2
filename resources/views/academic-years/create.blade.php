<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Academic Year') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('academic-years._form', [
                        'academicYear' => $academicYear,
                        'action' => route('academic-years.store'),
                        'submitLabel' => 'Create Academic Year',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
