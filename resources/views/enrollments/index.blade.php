<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Enrollments') }}</h2>
                <p class="mt-1 text-sm text-slate-500">Manage class enrollment by academic year so placement and finance reporting stay accurate.</p>
            </div>
            <a href="{{ route('enrollments.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Enroll Student</a>
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
                    @if ($enrollments->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No enrollments yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Create enrollment records to manage student class placement by academic year.</p>
                            <div class="mt-4">
                                <a href="{{ route('enrollments.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Create First Enrollment</a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Student</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Academic Year</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Grade / Section</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Fee Plan</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Enrollment Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($enrollments as $enrollment)
                                        <tr>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $enrollment->student?->full_name ?: '—' }}</div>
                                                <div class="text-sm text-slate-500">{{ $enrollment->student?->admission_no ?: 'No admission number' }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $enrollment->academicYear?->name ?: '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $enrollment->grade?->name ?: '—' }} / {{ $enrollment->section?->name ?: '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $enrollment->feePlan?->name ?: '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $enrollment->enrollment_date?->format('Y-m-d') ?: '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                    @if($enrollment->status === 'active') bg-emerald-100 text-emerald-700
                                                    @elseif($enrollment->status === 'completed') bg-sky-100 text-sky-700
                                                    @elseif($enrollment->status === 'inactive') bg-amber-100 text-amber-700
                                                    @else bg-slate-200 text-slate-700 @endif">
                                                    {{ ucfirst($enrollment->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('enrollments.edit', $enrollment) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Edit</a>
                                                    <form method="POST" action="{{ route('enrollments.destroy', $enrollment) }}" onsubmit="return confirm('Deactivate this enrollment?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">Deactivate</button>
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
