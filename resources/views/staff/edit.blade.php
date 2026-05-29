<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ __('Edit Staff Member') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('staff._form', [
                        'staff' => $staff,
                        'roles' => $roles,
                        'action' => route('staff.update', $staff),
                        'method' => 'PATCH',
                        'submitLabel' => 'Update Staff Member',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
