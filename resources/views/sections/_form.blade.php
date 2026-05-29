@props([
    'section',
    'grades',
    'rooms',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Section',
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="grade_id" :value="__('Grade')" />
            <select
                id="grade_id"
                name="grade_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                <option value="">Select Grade</option>
                @foreach ($grades as $grade)
                    <option value="{{ $grade->id }}" @selected((string) old('grade_id', $section->grade_id) === (string) $grade->id)>
                        {{ $grade->name }} ({{ $grade->code }})
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('grade_id')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            <select
                id="status"
                name="status"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                @php($selectedStatus = old('status', $section->status ?: 'active'))
                <option value="active" @selected($selectedStatus === 'active')>Active</option>
                <option value="draft" @selected($selectedStatus === 'draft')>Draft</option>
                <option value="closed" @selected($selectedStatus === 'closed')>Closed</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div>
            <x-input-label for="room_id" :value="__('Assigned Room')" />
            <select
                id="room_id"
                name="room_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">No room assigned</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}" @selected((string) old('room_id', $section->room_id) === (string) $room->id)>
                        {{ $room->name }} ({{ $room->code }})@if($room->building) - {{ $room->building }}@endif
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('room_id')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Section Name')" />
            <x-text-input
                id="name"
                name="name"
                type="text"
                class="mt-1 block w-full"
                :value="old('name', $section->name)"
                required
                placeholder="Section A"
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
                :value="old('code', $section->code)"
                required
                placeholder="A"
            />
            <x-input-error class="mt-2" :messages="$errors->get('code')" />
        </div>

        <div>
            <x-input-label for="capacity" :value="__('Capacity')" />
            <x-text-input
                id="capacity"
                name="capacity"
                type="number"
                min="1"
                class="mt-1 block w-full"
                :value="old('capacity', $section->capacity)"
                placeholder="40"
            />
            <x-input-error class="mt-2" :messages="$errors->get('capacity')" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('sections.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Cancel
        </a>
    </div>
</form>
