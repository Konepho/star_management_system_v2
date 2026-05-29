<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">ID Cards</h2>
                <p class="mt-1 text-sm text-slate-500">Print student or staff ID cards with a shared school layout.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if ($errors->has('selected_ids'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first('selected_ids') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-3 sm:p-4">
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('student-id-cards.index', ['audience' => 'students']) }}"
                           class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold {{ $audience === 'students' ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                            Student Cards
                        </a>
                        <a href="{{ route('student-id-cards.index', ['audience' => 'staff']) }}"
                           class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold {{ $audience === 'staff' ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                            Staff Cards
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('student-id-cards.index') }}" class="grid gap-4 md:grid-cols-4">
                        <input type="hidden" name="audience" value="{{ $audience }}">
                        <div>
                            <x-input-label for="search" :value="$audience === 'students' ? 'Search Student' : 'Search Staff'" />
                            <x-text-input
                                id="search"
                                name="search"
                                type="text"
                                class="mt-1 block w-full"
                                :value="$search"
                                :placeholder="$audience === 'students' ? 'Name or admission no' : 'Name, staff no, or designation'"
                            />
                        </div>

                        @if ($audience === 'students')
                            <div>
                                <x-input-label for="academic_year_id" value="Academic Year" />
                                <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Academic Years</option>
                                    @foreach ($academicYears as $academicYear)
                                        <option value="{{ $academicYear->id }}" @selected((string) $selectedAcademicYearId === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="grade_id" value="Grade" />
                                <select id="grade_id" name="grade_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Grades</option>
                                    @foreach ($grades as $grade)
                                        <option value="{{ $grade->id }}" @selected((string) $selectedGradeId === (string) $grade->id)>{{ $grade->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="section_id" value="Section" />
                                <select id="section_id" name="section_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Sections</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section->id }}" @selected((string) $selectedSectionId === (string) $section->id)>{{ $section->grade?->name }} / {{ $section->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div>
                                <x-input-label for="department" value="Department" />
                                <select id="department" name="department" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Departments</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department }}" @selected($selectedDepartment === $department)>{{ $department }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="md:col-span-4 flex flex-wrap items-center justify-end gap-3">
                            @if ($search !== '' || $selectedAcademicYearId || $selectedGradeId || $selectedSectionId || $selectedDepartment)
                                <a href="{{ route('student-id-cards.index', ['audience' => $audience]) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    Clear
                                </a>
                            @endif
                            <x-primary-button>Filter</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (($audience === 'students' && $students->isEmpty()) || ($audience === 'staff' && $staff->isEmpty()))
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No {{ $audience }} found</h3>
                            <p class="mt-2 text-sm text-slate-500">Try adjusting the filters to see more {{ $audience }} for ID card printing.</p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('student-id-cards.bulk-print') }}" target="_blank" class="space-y-4">
                            @csrf
                            <input type="hidden" name="audience" value="{{ $audience }}">

                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm text-slate-500">Select {{ $audience }} and print multiple cards at once.</p>
                                <x-primary-button>Print Selected Cards</x-primary-button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                                <input type="checkbox" onclick="document.querySelectorAll('.student-id-card-checkbox').forEach((checkbox) => checkbox.checked = this.checked)">
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $audience === 'students' ? 'Admission No' : 'Staff No' }}</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $audience === 'students' ? 'Student' : 'Staff' }}</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $audience === 'students' ? 'Academic Year' : 'Department' }}</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $audience === 'students' ? 'Grade / Section' : 'Designation' }}</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @if ($audience === 'students')
                                            @foreach ($students as $student)
                                                @php($currentEnrollment = $student->currentEnrollment())
                                                <tr>
                                                    <td class="px-4 py-4 text-sm text-slate-600">
                                                        <input class="student-id-card-checkbox" type="checkbox" name="selected_ids[]" value="{{ $student->id }}">
                                                    </td>
                                                    <td class="px-4 py-4 text-sm text-slate-600">{{ $student->admission_no }}</td>
                                                    <td class="px-4 py-4">
                                                        <div class="font-medium text-slate-900">{{ $student->preferred_name ?: $student->name_en ?: $student->full_name }}</div>
                                                        <div class="text-sm text-slate-500">{{ $student->name_mm ?: 'No Burmese name' }}</div>
                                                    </td>
                                                    <td class="px-4 py-4 text-sm text-slate-600">{{ $currentEnrollment?->academicYear?->name ?: '—' }}</td>
                                                    <td class="px-4 py-4 text-sm text-slate-600">{{ $currentEnrollment?->grade?->name ?: '—' }} / {{ $currentEnrollment?->section?->name ?: '—' }}</td>
                                                    <td class="px-4 py-4 text-sm text-slate-600">{{ ucfirst($student->status) }}</td>
                                                    <td class="px-4 py-4 text-right">
                                                        <a href="{{ route('student-id-cards.print', $student) }}" target="_blank" class="text-sm font-medium text-sky-700 hover:text-sky-600">
                                                            Print Card
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            @foreach ($staff as $member)
                                                <tr>
                                                    <td class="px-4 py-4 text-sm text-slate-600">
                                                        <input class="student-id-card-checkbox" type="checkbox" name="selected_ids[]" value="{{ $member->id }}">
                                                    </td>
                                                    <td class="px-4 py-4 text-sm text-slate-600">{{ $member->staff_no }}</td>
                                                    <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $member->displayName() ?: '—' }}</div>
                                                        <div class="text-sm text-slate-500">{{ $member->email ?: ($member->phone ?: 'No contact') }}</div>
                                                    </td>
                                                    <td class="px-4 py-4 text-sm text-slate-600">{{ $member->department ?: '—' }}</td>
                                                    <td class="px-4 py-4 text-sm text-slate-600">{{ $member->designation ?: '—' }}</td>
                                                    <td class="px-4 py-4 text-sm text-slate-600">{{ ucfirst($member->status) }}</td>
                                                    <td class="px-4 py-4 text-right">
                                                        <a href="{{ route('student-id-cards.print-staff', $member) }}" target="_blank" class="text-sm font-medium text-sky-700 hover:text-sky-600">
                                                            Print Card
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
