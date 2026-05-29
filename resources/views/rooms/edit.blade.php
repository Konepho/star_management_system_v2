<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Edit Room') }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">Update room details for school operations and future timetable use.</p>
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
                        'action' => route('rooms.update', $room),
                        'method' => 'PATCH',
                        'submitLabel' => 'Update Room',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
