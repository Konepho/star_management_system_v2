<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">Extra Class Registrations</h2>
                <p class="mt-1 text-sm text-slate-700">Register students into yearly extra class offerings and bill them from invoices.</p>
            </div>
            <a href="{{ route('student-extra-class-registrations.create') }}" class="inline-flex w-full items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600 sm:w-auto">
                Register Student
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
                    <form method="GET" action="{{ route('student-extra-class-registrations.index') }}" class="mb-6 flex flex-col gap-3 sm:flex-row">
                        <x-text-input name="search" type="text" class="block w-full" :value="$search" placeholder="Search student, admission no, or extra class" />
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Filter</button>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Student</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Academic Year</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Extra Class</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Fee</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($registrations as $registration)
                                    @php($student = $registration->student)
                                    <tr>
                                        <td class="px-4 py-4">
                                            <div class="font-medium text-slate-900">{{ trim(collect([$student?->preferred_name, $student?->name_en ?: $student?->full_name, $student?->name_mm])->filter()->unique()->implode(' / ')) }}</div>
                                            <div class="text-sm text-slate-500">{{ $student?->admission_no }}</div>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $registration->academicYear?->name }}</td>
                                        <td class="px-4 py-4">
                                            <div class="font-medium text-slate-900">{{ $registration->offering?->display_name }}</div>
                                            <div class="text-sm text-slate-500">{{ $registration->offering?->catalog?->code }}</div>
                                        </td>
                                        <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ number_format((float) $registration->offering?->fee_amount, 2) }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold @if($registration->status === 'active') bg-emerald-100 text-emerald-700 @elseif($registration->status === 'completed') bg-sky-100 text-sky-700 @else bg-slate-200 text-slate-700 @endif">
                                                {{ ucfirst($registration->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="{{ route('student-extra-class-registrations.edit', $registration) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Edit</a>
                                                <form method="POST" action="{{ route('student-extra-class-registrations.destroy', $registration) }}" onsubmit="return confirm('Cancel this registration?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">Cancel</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No extra class registrations found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
