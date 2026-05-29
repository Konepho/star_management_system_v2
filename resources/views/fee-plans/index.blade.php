<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ __('Fee Plans') }}</h2>
                <p class="mt-1 text-sm text-slate-700">Create named fee packages like Primary BASIC and reuse your existing fee structures underneath.</p>
            </div>
            <a href="{{ route('fee-plans.create') }}" class="inline-flex w-full items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600 sm:w-auto">
                Add Fee Plan
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
                    @if ($feePlans->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No fee plans yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Create named plans like Primary BASIC, Secondary STANDARD, or IGCSE Foundation.</p>
                            <div class="mt-4">
                                <a href="{{ route('fee-plans.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Create First Fee Plan</a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Academic Year</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Plan</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Group</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Fee Structures</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($feePlans as $feePlan)
                                        <tr>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $feePlan->academicYear?->name }}</td>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $feePlan->name }}</div>
                                                <div class="text-sm text-slate-500">{{ $feePlan->code }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $feePlan->grade_group_label ?: 'All / Mixed' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                {{ $feePlan->feeStructures->count() }} linked
                                                <div class="text-xs text-slate-500">
                                                    {{ $feePlan->feeStructures->take(3)->map(fn ($feeStructure) => $feeStructure->feeCategory?->name)->filter()->join(', ') }}
                                                    @if ($feePlan->feeStructures->count() > 3)
                                                        ...
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                    @if($feePlan->status === 'active') bg-emerald-100 text-emerald-700
                                                    @else bg-slate-200 text-slate-700 @endif">
                                                    {{ ucfirst($feePlan->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('fee-plans.edit', $feePlan) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Edit</a>
                                                    <form method="POST" action="{{ route('fee-plans.destroy', $feePlan) }}" onsubmit="return confirm('Deactivate this fee plan?');">
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
