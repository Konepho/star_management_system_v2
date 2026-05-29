<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Student Daily Report</h2>
                <p class="text-sm text-slate-600">Update the remark for this student.</p>
            </div>
            <a href="{{ route('student-daily-reports.index') }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                Back to Reports
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('student-daily-reports.update', $report) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    @include('student-daily-reports._form', [
                        'report' => $report,
                        'studentOptions' => $studentOptions,
                    ])

                    <div class="flex justify-end">
                        <x-primary-button>Update Report</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
