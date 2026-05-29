<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Take Class Attendance') }}</h2>
                <p class="mt-1 text-sm text-slate-500">Choose an academic year, class, and date to record the full register in one screen.</p>
            </div>
            <a href="{{ route('attendances.index', ['academic_year_id' => $selectedAcademicYearId, 'section_id' => $selectedSectionId, 'attendance_date' => $selectedDate]) }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                View Saved Attendance
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            @if ($errors->has('attendances'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first('attendances') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('attendances.create') }}" class="grid gap-4 md:grid-cols-4 md:items-end">
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
                            <x-primary-button>Load Class Register</x-primary-button>
                            <a href="{{ route('attendances.create') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            @if (! $selectedAcademicYearId || ! $selectedSectionId)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 text-center">
                        <h3 class="text-lg font-semibold text-slate-900">Choose the class first</h3>
                        <p class="mt-2 text-sm text-slate-500">Attendance is now recorded class-by-class, so teachers can mark the whole register in one save.</p>
                    </div>
                </div>
            @elseif ($registerEnrollments->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 text-center">
                        <h3 class="text-lg font-semibold text-slate-900">No enrolled students found</h3>
                        <p class="mt-2 text-sm text-slate-500">This class has no active enrollments for the selected academic year yet.</p>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">{{ $selectedSection?->grade?->name }} / {{ $selectedSection?->name }}</h3>
                                <p class="text-sm text-slate-500">{{ $academicYears->firstWhere('id', $selectedAcademicYearId)?->name }} • {{ $selectedDate }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                {{ $registerEnrollments->count() }} students in register
                            </div>
                        </div>
                    </div>
                    <div class="p-6 text-gray-900">
                        <form method="POST" action="{{ route('attendances.store') }}">
                            @csrf
                            <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYearId }}">
                            <input type="hidden" name="section_id" value="{{ $selectedSectionId }}">
                            <input type="hidden" name="attendance_date" value="{{ $selectedDate }}">

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
                                        @foreach ($registerEnrollments as $index => $enrollment)
                                            @php($existingAttendance = $existingAttendances->get($enrollment->student_id))
                                            <tr>
                                                <td class="px-4 py-4 align-top">
                                                    <div class="font-medium text-slate-900">{{ $enrollment->student?->full_name }}</div>
                                                    @if ($enrollment->student?->name_mm)
                                                        <div class="text-sm text-slate-500">{{ $enrollment->student->name_mm }}</div>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-4 align-top text-sm text-slate-600">{{ $enrollment->student?->admission_no ?: '—' }}</td>
                                                <td class="px-4 py-4 align-top">
                                                    <input type="hidden" name="attendances[{{ $index }}][student_id]" value="{{ $enrollment->student_id }}">
                                                    <select name="attendances[{{ $index }}][status]" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                                        @php($selectedStatus = old("attendances.$index.status", $existingAttendance?->status ?: 'present'))
                                                        <option value="present" @selected($selectedStatus === 'present')>Present</option>
                                                        <option value="absent" @selected($selectedStatus === 'absent')>Absent</option>
                                                        <option value="late" @selected($selectedStatus === 'late')>Late</option>
                                                    </select>
                                                    @error("attendances.$index.status")
                                                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                                    @enderror
                                                </td>
                                                <td class="px-4 py-4 align-top">
                                                    <input
                                                        type="text"
                                                        name="attendances[{{ $index }}][remarks]"
                                                        value="{{ old("attendances.$index.remarks", $existingAttendance?->remarks) }}"
                                                        class="block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                                        placeholder="Optional remark"
                                                    >
                                                    @error("attendances.$index.remarks")
                                                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                                    @enderror
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6 flex items-center justify-end">
                                <x-primary-button>Save Class Attendance</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
