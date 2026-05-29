<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ __('Assign Student Discount') }}</h2>
                <p class="mt-1 text-sm text-slate-700">Assign a reusable scholarship or discount to a student so future invoices can apply it automatically.</p>
            </div>
            <a href="{{ route('student-discounts.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Back to Student Discounts
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('student-discounts._form', [
                        'studentDiscount' => $studentDiscount,
                        'students' => $students,
                        'discountDefinitions' => $discountDefinitions,
                        'action' => route('student-discounts.store'),
                        'submitLabel' => 'Assign Student Discount',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
