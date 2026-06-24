<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ __('Student Discounts') }}</h2>
                <p class="mt-1 text-sm text-slate-700">Manage student-level recurring scholarships and discounts that can auto-apply to future invoices.</p>
            </div>
            <a href="{{ route('student-discounts.create') }}" class="inline-flex w-full items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600 sm:w-auto">
                Assign Student Discount
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
                    @if ($studentDiscounts->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No student discounts yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Assign saved scholarships or discounts to students so invoice generation can apply them automatically.</p>
                            <div class="mt-4">
                                <a href="{{ route('student-discounts.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Assign First Student Discount</a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">No.</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Student</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Discount</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Period</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Notes</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($studentDiscounts as $studentDiscount)
                                        <tr>
                                            <td class="px-4 py-4 text-sm font-medium text-slate-500">{{ $studentDiscounts->firstItem() + $loop->index }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-900">
                                                <div class="font-medium">{{ $studentDiscount->student?->full_name }}</div>
                                                <div class="text-xs text-slate-500">{{ $studentDiscount->student?->admission_no }}{{ $studentDiscount->student?->grade ? ' - '.$studentDiscount->student->grade->name : '' }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <div class="font-medium text-slate-900">{{ $studentDiscount->discountDefinition?->name }}</div>
                                                <div class="text-xs text-slate-500">
                                                    {{ $studentDiscount->discountDefinition ? (\App\Models\DiscountDefinition::typeOptions()[$studentDiscount->discountDefinition->discount_type] ?? ucfirst($studentDiscount->discountDefinition->discount_type)) : '—' }}
                                                    @if ($studentDiscount->discountDefinition)
                                                        - {{ number_format((float) $studentDiscount->discountDefinition->value, 2) }}{{ $studentDiscount->discountDefinition->discount_type === \App\Models\DiscountDefinition::TYPE_PERCENTAGE ? '%' : '' }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                {{ $studentDiscount->start_date?->format('Y-m-d') ?? '—' }}
                                                <div class="text-xs text-slate-500">
                                                    to {{ $studentDiscount->end_date?->format('Y-m-d') ?? 'Open-ended' }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                    @if($studentDiscount->status === 'active') bg-emerald-100 text-emerald-700
                                                    @else bg-slate-200 text-slate-700 @endif">
                                                    {{ ucfirst($studentDiscount->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $studentDiscount->notes ?: '—' }}</td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('student-discounts.edit', $studentDiscount) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Edit</a>
                                                    <form method="POST" action="{{ route('student-discounts.destroy', $studentDiscount) }}" onsubmit="return confirm('Deactivate this student discount?');">
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
                        <div class="mt-4">
                            {{ $studentDiscounts->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
