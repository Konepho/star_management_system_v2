@props([
    'room',
    'roomTypes',
    'statusOptions',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Room',
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="name" :value="__('Room Name')" />
            <x-text-input
                id="name"
                name="name"
                type="text"
                class="mt-1 block w-full"
                :value="old('name', $room->name)"
                required
                placeholder="Room 101"
            />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="code" :value="__('Code')" />
            <x-text-input
                id="code"
                name="code"
                type="text"
                class="mt-1 block w-full"
                :value="old('code', $room->code)"
                required
                placeholder="RM-101"
            />
            <x-input-error class="mt-2" :messages="$errors->get('code')" />
        </div>

        <div>
            <x-input-label for="building" :value="__('Building / Block')" />
            <x-text-input
                id="building"
                name="building"
                type="text"
                class="mt-1 block w-full"
                :value="old('building', $room->building)"
                placeholder="Main Building"
            />
            <x-input-error class="mt-2" :messages="$errors->get('building')" />
        </div>

        <div>
            <x-input-label for="floor" :value="__('Floor')" />
            <x-text-input
                id="floor"
                name="floor"
                type="text"
                class="mt-1 block w-full"
                :value="old('floor', $room->floor)"
                placeholder="1"
            />
            <x-input-error class="mt-2" :messages="$errors->get('floor')" />
        </div>

        <div>
            <x-input-label for="capacity" :value="__('Capacity')" />
            <x-text-input
                id="capacity"
                name="capacity"
                type="number"
                min="1"
                class="mt-1 block w-full"
                :value="old('capacity', $room->capacity)"
                placeholder="40"
            />
            <x-input-error class="mt-2" :messages="$errors->get('capacity')" />
        </div>

        <div>
            <x-input-label for="room_type" :value="__('Room Type')" />
            <select
                id="room_type"
                name="room_type"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                <option value="">Select Room Type</option>
                @foreach ($roomTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('room_type', $room->room_type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('room_type')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            <select
                id="status"
                name="status"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $room->status ?: 'active') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('rooms.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Cancel
        </a>
    </div>
</form>
