<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Students') }}</h2>
                <p class="mt-1 text-sm text-slate-500">Manage student records, enrollment placement, and status.</p>
            </div>
            <a href="{{ route('students.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Add Student</a>
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
                    <div class="mb-6 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                        Student records now hold profile information only. Use <a href="{{ route('enrollments.index') }}" class="font-semibold underline">Enrollments</a> to assign academic year, grade, section, and future fee plan placement.
                    </div>

                    @if ($students->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No students yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Create your first student profile, then use enrollments to place the student into class and finance setup.</p>
                            <div class="mt-4">
                                <a href="{{ route('students.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Create First Student</a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Admission No</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Preferred Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Academic Year</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Grade / Section</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($students as $student)
                                        @php($currentEnrollment = $student->currentEnrollment())
                                        <tr>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $student->admission_no }}</td>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $student->name_en ?: $student->full_name }}</div>
                                                <div class="text-sm text-slate-500">{{ $student->name_mm ?: 'No Burmese name' }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <div>{{ $student->preferred_name ?: '—' }}</div>
                                                <div class="text-xs text-slate-500">{{ $student->contact_number ?: $student->phone ?: 'No contact' }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $currentEnrollment?->academicYear?->name ?: '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                {{ $currentEnrollment?->grade?->name ?: '—' }} / {{ $currentEnrollment?->section?->name ?: '—' }}
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                    @if($student->status === 'active') bg-emerald-100 text-emerald-700
                                                    @elseif($student->status === 'inactive') bg-amber-100 text-amber-700
                                                    @elseif($student->status === 'graduated') bg-sky-100 text-sky-700
                                                    @elseif($student->status === 'archived') bg-rose-100 text-rose-700
                                                    @else bg-slate-200 text-slate-700 @endif">
                                                    {{ ucfirst($student->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('students.edit', $student) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Edit</a>
                                                    @if ($student->status !== 'archived')
                                                        <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Archive this student? Their academic and finance history will be kept.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">Archive</button>
                                                        </form>
                                                    @else
                                                        <span class="text-sm font-medium text-slate-400">Archived</span>
                                                    @endif
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
