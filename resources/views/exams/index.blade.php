<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Exams') }}</h2>
                <p class="mt-1 text-sm text-slate-500">Manage exam sessions before entering subject marks and publishing results.</p>
            </div>
            <a href="{{ route('exams.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Add Exam</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($exams->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No exams yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Create exam sessions now so the next step can be mark entry and exam records.</p>
                            <div class="mt-4">
                                <a href="{{ route('exams.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Create First Exam</a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Code</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Academic Year</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Term</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($exams as $exam)
                                        <tr>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $exam->name }}</div>
                                                <div class="text-sm text-slate-500">
                                                    @if ($exam->start_date || $exam->end_date)
                                                        {{ optional($exam->start_date)->format('d M Y') ?: '—' }} to {{ optional($exam->end_date)->format('d M Y') ?: '—' }}
                                                    @else
                                                        No exam dates yet
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $exam->code }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $exam->academicYear?->name ?: '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $exam->term ?: '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                    @if($exam->status === 'published') bg-emerald-100 text-emerald-700
                                                    @elseif($exam->status === 'closed') bg-slate-200 text-slate-700
                                                    @else bg-amber-100 text-amber-700 @endif">
                                                    {{ ucfirst($exam->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('exams.edit', $exam) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Edit</a>
                                                    <form method="POST" action="{{ route('exams.destroy', $exam) }}" onsubmit="return confirm('Close this exam?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">Close</button>
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
