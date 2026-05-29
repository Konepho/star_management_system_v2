<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Edit External Exam Registration</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('external-exam-registrations._form', [
                        'registration' => $registration,
                        'students' => $students,
                        'sessions' => $sessions,
                        'action' => route('external-exam-registrations.update', $registration),
                        'method' => 'PATCH',
                        'submitLabel' => 'Update Registration',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
