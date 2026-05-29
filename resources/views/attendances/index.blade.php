<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Attendance') }}</h2>
                <p class="mt-1 text-sm text-slate-500">Review one class register at a time and jump straight back into class attendance entry.</p>
            </div>
            <a href="{{ route('attendances.create', ['academic_year_id' => $selectedAcademicYearId, 'section_id' => $selectedSectionId, 'attendance_date' => $selectedDate]) }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                Take Class Attendance
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('attendances.index') }}" class="grid gap-4 md:grid-cols-4 md:items-end">
                        <div>
                            <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                            <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                <option value="">Select academic year</option>
                                @foreach ($academicYears as $academicYear)
                                    <option value="{{ $academicYear->id }}" @selected((string) $selectedAcademicYearId === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="section_id" :value="__('Class / Section')" />
                            <select id="section_id" name="section_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                <option value="">Select class</option>
                                @foreach ($sections as $section)
                                    <option value="{{ $section->id }}" @selected((string) $selectedSectionId === (string) $section->id)>{{ $section->grade?->name }} / {{ $section->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="attendance_date" :value="__('Attendance Date')" />
                            <x-text-input id="attendance_date" name="attendance_date" type="date" class="mt-1 block w-full" :value="$selectedDate" />
                        </div>
                        <div class="flex gap-3">
                            <x-primary-button>Filter Register</x-primary-button>
                            <a href="{{ route('attendances.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            @if (! $selectedAcademicYearId || ! $selectedSectionId)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 text-center">
                        <h3 class="text-lg font-semibold text-slate-900">Choose a class to review attendance</h3>
                        <p class="mt-2 text-sm text-slate-500">The attendance workflow is now class-based, so each register is reviewed by academic year, class, and date.</p>
                    </div>
                </div>
            @else
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-sm font-medium text-slate-500">Class</div>
                        <div class="mt-2 text-lg font-semibold text-slate-900">{{ $selectedSection?->grade?->name }} / {{ $selectedSection?->name }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-sm font-medium text-slate-500">Register Date</div>
                        <div class="mt-2 text-lg font-semibold text-slate-900">{{ $selectedDate }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-sm font-medium text-slate-500">Students in Class</div>
                        <div class="mt-2 text-lg font-semibold text-slate-900">{{ $registerEnrollments->count() }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-sm font-medium text-slate-500">Recorded Entries</div>
                        <div class="mt-2 text-lg font-semibold text-slate-900">{{ $attendanceRecords->count() }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Saved Class Register</h3>
                                <p class="text-sm text-slate-500">{{ $academicYears->firstWhere('id', $selectedAcademicYearId)?->name }} • {{ $selectedSection?->grade?->name }} / {{ $selectedSection?->name }} • {{ $selectedDate }}</p>
                            </div>
                            <a href="{{ route('attendances.create', ['academic_year_id' => $selectedAcademicYearId, 'section_id' => $selectedSectionId, 'attendance_date' => $selectedDate]) }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Edit Class Register
                            </a>
                        </div>
                    </div>
                    <div class="p-6 text-gray-900">
                        @if ($attendanceRecords->isEmpty())
                            <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                                <h3 class="text-lg font-semibold text-slate-900">No attendance recorded yet</h3>
                                <p class="mt-2 text-sm text-slate-500">Open the class register and save attendance for this class and date.</p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Student</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Admission No</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach ($attendanceRecords as $attendance)
                                            <tr>
                                                <td class="px-4 py-4">
                                                    <div class="font-medium text-slate-900">{{ $attendance->student?->full_name }}</div>
                                                    @if ($attendance->student?->name_mm)
                                                        <div class="text-sm text-slate-500">{{ $attendance->student->name_mm }}</div>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-4 text-sm text-slate-600">{{ $attendance->student?->admission_no ?: '—' }}</td>
                                                <td class="px-4 py-4 text-sm text-slate-600">
                                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                        @if($attendance->status === 'present') bg-emerald-100 text-emerald-700
                                                        @elseif($attendance->status === 'absent') bg-rose-100 text-rose-700
                                                        @elseif($attendance->status === 'late') bg-amber-100 text-amber-700
                                                        @else bg-sky-100 text-sky-700 @endif">
                                                        {{ ucfirst($attendance->status) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4 text-sm text-slate-600">{{ $attendance->remarks ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
