<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Sections') }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">Manage grade-based sections for class organization and enrollment.</p>
            </div>
            <a href="{{ route('sections.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                Add Section
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
                    @if ($sections->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No sections yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Create sections after setting up grades so student enrollment can be organized properly.</p>
                            <div class="mt-4">
                                <a href="{{ route('sections.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                                    Create First Section
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Grade</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Code</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Room</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Capacity</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($sections as $section)
                                        <tr>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                {{ $section->grade->name }}
                                            </td>
                                            <td class="px-4 py-4 font-medium text-slate-900">{{ $section->name }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $section->code }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                @if ($section->room)
                                                    {{ $section->room->name }} ({{ $section->room->code }})
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $section->capacity ?: '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                    @if($section->status === 'active') bg-emerald-100 text-emerald-700
                                                    @elseif($section->status === 'closed') bg-slate-200 text-slate-700
                                                    @else bg-amber-100 text-amber-700 @endif">
                                                    {{ ucfirst($section->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('sections.edit', $section) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">
                                                        Edit
                                                    </a>
                                                    <form method="POST" action="{{ route('sections.destroy', $section) }}" onsubmit="return confirm('Close this section?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">
                                                            Close
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
