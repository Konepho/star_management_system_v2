<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">
                    {{ __('Academic Years') }}
                </h2>
                <p class="mt-1 text-sm text-slate-700">Manage the school years used by enrollment, fees, and exams.</p>
            </div>
            <a href="{{ route('academic-years.create') }}" class="inline-flex w-full items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600 sm:w-auto">
                Add Academic Year
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
                    @if ($academicYears->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No academic years yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Create your first academic year to start organizing school operations.</p>
                            <div class="mt-4">
                                <a href="{{ route('academic-years.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                                    Create First Academic Year
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Period</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Current</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($academicYears as $academicYear)
                                        <tr>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $academicYear->name }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                {{ $academicYear->start_date->format('d M Y') }} to {{ $academicYear->end_date->format('d M Y') }}
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                    @if($academicYear->status === 'active') bg-emerald-100 text-emerald-700
                                                    @elseif($academicYear->status === 'closed') bg-slate-200 text-slate-700
                                                    @else bg-amber-100 text-amber-700 @endif">
                                                    {{ ucfirst($academicYear->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                @if ($academicYear->is_current)
                                                    <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">Current</span>
                                                @else
                                                    <span class="text-slate-400">No</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('academic-years.edit', $academicYear) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">
                                                        Edit
                                                    </a>
                                                    <form method="POST" action="{{ route('academic-years.destroy', $academicYear) }}" onsubmit="return confirm('Close this academic year?');">
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
