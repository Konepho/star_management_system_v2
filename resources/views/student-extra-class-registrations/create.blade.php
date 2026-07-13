<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">Register Student for Extra Class</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('student-extra-class-registrations._form', [
                        'registration' => $registration,
                        'students' => $students,
                        'academicYears' => $academicYears,
                        'offerings' => $offerings,
                        'action' => route('student-extra-class-registrations.store'),
                        'submitLabel' => 'Create Registration',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
