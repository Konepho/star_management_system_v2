<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">Extra Class Offerings</h2>
                <p class="mt-1 text-sm text-slate-700">Set the academic-year fee for each extra class without overwriting old years.</p>
            </div>
            <a href="{{ route('extra-class-offerings.create') }}" class="inline-flex w-full items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600 sm:w-auto">
                Add Offering
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
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Academic Year</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Class</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Fee</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Registrations</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($offerings as $offering)
                                    <tr>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $offering->academicYear?->name }}</td>
                                        <td class="px-4 py-4">
                                            <div class="font-medium text-slate-900">{{ $offering->display_name }}</div>
                                            <div class="text-sm text-slate-500">{{ $offering->catalog?->code }}{{ $offering->feeCategory ? ' · ' . $offering->feeCategory->name : '' }}</div>
                                        </td>
                                        <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ number_format((float) $offering->fee_amount, 2) }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $offering->registrations_count }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold @if($offering->status === 'active') bg-emerald-100 text-emerald-700 @else bg-slate-200 text-slate-700 @endif">
                                                {{ ucfirst($offering->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="{{ route('extra-class-offerings.edit', $offering) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Edit</a>
                                                <form method="POST" action="{{ route('extra-class-offerings.destroy', $offering) }}" onsubmit="return confirm('Deactivate this offering?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-600">Deactivate</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No extra class offerings yet.</td>
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
