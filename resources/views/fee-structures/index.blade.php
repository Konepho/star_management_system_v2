<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ __('Fee Structures') }}</h2>
                <p class="mt-1 text-sm text-slate-700">Assign fee amounts by academic year, school-wide scope, grade group, or a specific grade.</p>
            </div>
            <a href="{{ route('fee-structures.create') }}" class="inline-flex w-full items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600 sm:w-auto">
                Add Fee Structure
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
                    @if ($feeStructures->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No fee structures yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Create fee setups by year and grade once your fee categories are ready.</p>
                            <div class="mt-4">
                                <a href="{{ route('fee-structures.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Create First Fee Structure</a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Academic Year</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Scope</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Fee Category</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Cycle</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Optional</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($feeStructures as $feeStructure)
                                        <tr>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $feeStructure->academicYear?->name }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $feeStructure->scope_label }}</td>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $feeStructure->feeCategory?->name }}</div>
                                                <div class="text-sm text-slate-500">{{ $feeStructure->feeCategory?->code }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                {{ $feeStructure->billing_cycle_label }}
                                                @if ($feeStructure->billing_cycle === 'installment')
                                                    <div class="text-xs text-slate-500">{{ $feeStructure->installments->count() }} payments</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-sm font-medium text-slate-900">
                                                {{ number_format((float) $feeStructure->amount, 2) }}
                                                @if ($feeStructure->billing_cycle === 'installment')
                                                    <div class="text-xs font-normal text-slate-500">
                                                        @foreach ($feeStructure->installments as $installment)
                                                            {{ $installment->installment_no }}: {{ number_format((float) $installment->amount, 2) }}@if (! $loop->last), @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $feeStructure->is_optional ? 'Yes' : 'No' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                    @if($feeStructure->status === 'active') bg-emerald-100 text-emerald-700
                                                    @else bg-slate-200 text-slate-700 @endif">
                                                    {{ ucfirst($feeStructure->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('fee-structures.edit', $feeStructure) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Edit</a>
                                                    <form method="POST" action="{{ route('fee-structures.destroy', $feeStructure) }}" onsubmit="return confirm('Deactivate this fee structure?');">
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
