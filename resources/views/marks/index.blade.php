<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Marks') }}</h2>
                <p class="mt-1 text-sm text-slate-500">Record student scores by exam and subject.</p>
            </div>
            <a href="{{ route('marks.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Add Mark</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($marks->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No marks yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Create mark records now that exams, students, and subjects are ready.</p>
                            <div class="mt-4">
                                <a href="{{ route('marks.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Create First Mark</a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Exam</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Student</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Subject</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Score</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Grade</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($marks as $mark)
                                        @php($currentEnrollment = $mark->student?->currentEnrollment())
                                        <tr>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $mark->exam?->name }}</td>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $mark->student?->full_name }}</div>
                                                <div class="text-sm text-slate-500">{{ $currentEnrollment?->grade?->name ?: '—' }} / {{ $currentEnrollment?->section?->name ?: '—' }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $mark->subject?->name }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ number_format((float) $mark->score, 2) }} / {{ number_format((float) $mark->max_score, 2) }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $mark->grade_letter ?: '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                    @if($mark->status === 'published') bg-emerald-100 text-emerald-700
                                                    @elseif($mark->status === 'reviewed') bg-sky-100 text-sky-700
                                                    @else bg-amber-100 text-amber-700 @endif">
                                                    {{ ucfirst($mark->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('marks.edit', $mark) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Edit</a>
                                                    <form method="POST" action="{{ route('marks.destroy', $mark) }}" onsubmit="return confirm('Archive this mark record?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">Archive</button>
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
