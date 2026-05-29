<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Rooms') }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">Manage classrooms and operational spaces for daily school use.</p>
            </div>
            <a href="{{ route('rooms.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                Add Room
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($rooms->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No rooms yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Create rooms now so sections, exams, and future timetables can map to real school spaces.</p>
                            <div class="mt-4">
                                <a href="{{ route('rooms.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                                    Create First Room
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Room</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Code</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Building</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Floor</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Capacity</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($rooms as $room)
                                        <tr>
                                            <td class="px-4 py-4 font-medium text-slate-900">{{ $room->name }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $room->code }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $room->building ?: '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $room->floor ?: '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ \App\Models\Room::typeOptions()[$room->room_type] ?? ucfirst($room->room_type) }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $room->capacity ?: '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                    @if($room->status === 'active') bg-emerald-100 text-emerald-700
                                                    @elseif($room->status === 'maintenance') bg-amber-100 text-amber-700
                                                    @else bg-slate-200 text-slate-700 @endif">
                                                    {{ \App\Models\Room::statusOptions()[$room->status] ?? ucfirst($room->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('rooms.edit', $room) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">
                                                        Edit
                                                    </a>
                                                    <form method="POST" action="{{ route('rooms.destroy', $room) }}" onsubmit="return confirm('Deactivate this room?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">
                                                            Deactivate
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
