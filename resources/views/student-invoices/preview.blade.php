<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Invoice Preview') }}</h2>
    </x-slot>

    @php($studentDisplayName = trim(collect([
        trim((string) $student->preferred_name),
        trim((string) ($student->name_en ?: $student->full_name)),
        trim((string) $student->name_mm),
    ])->filter()->unique()->implode(' / ')) ?: '—')

    <div class="py-12">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">{{ $studentDisplayName }}</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ $activeEnrollment?->academicYear?->name ?? 'No active enrollment' }}
                                @if ($activeEnrollment?->grade)
                                    - {{ $activeEnrollment->grade->name }}
                                @endif
                                @if ($activeEnrollment?->section)
                                    - {{ $activeEnrollment->section->name }}
                                @endif
                            </p>
                            @if ($activeEnrollment?->feePlan)
                                <p class="mt-1 text-sm font-medium text-sky-700">Fee Plan: {{ $activeEnrollment->feePlan->name }}</p>
                            @else
                                <p class="mt-1 text-sm font-medium text-amber-700">No active fee plan is assigned to this enrollment yet.</p>
                            @endif
                        </div>
                        <a href="{{ route('student-invoices.create') }}" class="inline-flex items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600">
                            Generate Invoice
                        </a>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Fee Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Scope</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Cycle</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($feeStructures as $feeStructure)
                                    <tr>
                                        <td class="px-4 py-4 text-sm text-slate-900">{{ $feeStructure->feeCategory?->name }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $feeStructure->scope_label }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $feeStructure->billing_cycle_label }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-900">
                                            {{ number_format((float) $feeStructure->amount, 2) }}
                                            @if ($feeStructure->billing_cycle === 'installment')
                                                <div class="text-xs text-slate-500">
                                                    @foreach ($feeStructure->installments as $installment)
                                                        {{ $installment->installment_no }}: {{ number_format((float) $installment->amount, 2) }}@if (! $loop->last), @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">No active fee structures match this student yet.</td>
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
