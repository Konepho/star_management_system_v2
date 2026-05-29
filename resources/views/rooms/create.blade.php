<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Add Room') }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">Create rooms for classrooms, offices, labs, and other school spaces.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('rooms._form', [
                        'room' => $room,
                        'roomTypes' => $roomTypes,
                        'statusOptions' => $statusOptions,
                        'action' => route('rooms.store'),
                        'method' => 'POST',
                        'submitLabel' => 'Save Room',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
