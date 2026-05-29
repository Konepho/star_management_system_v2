<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Financial Report') }}</h2>
                <p class="mt-1 text-sm text-slate-700">See projected school income from enrolled students, plus billed, collected, and outstanding amounts.</p>
            </div>
            <a href="{{ route('student-invoices.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Open Student Invoices
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 shadow-xl">
                <div class="grid gap-8 px-6 py-8 text-white lg:grid-cols-[1.25fr_0.75fr] lg:px-8">
                    <div>
                        <div class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-emerald-100">
                            Finance Intelligence
                        </div>
                        <h3 class="mt-4 text-3xl font-semibold tracking-tight">Track projected income and real collections in one place.</h3>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200">
                            This report calculates projected core income from enrolled students and active non-optional fee structures, then compares that with actual invoices and payments.
                        </p>

                        <div class="mt-6 grid gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Academic Year</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $selectedAcademicYear->name }}</div>
                                <div class="mt-1 text-xs text-slate-300">{{ $studentsCount }} active enrolled student(s)</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Selected Period</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $periodLabel }}</div>
                                <div class="mt-1 text-xs text-slate-300">Month, 3-month quarter, or full academic year</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Collection Rate</div>
                                <div class="mt-2 text-2xl font-semibold">{{ number_format($collectionRate, 1) }}%</div>
                                <div class="mt-1 text-xs text-slate-300">Collected against projected amount</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur">
                        <div>
                            <div class="text-sm font-semibold text-white">Report Filters</div>
                            <div class="mt-1 text-xs text-slate-300">Switch between month, quarter, and academic-year reporting.</div>
                        </div>

                        <form method="GET" action="{{ route('reports.financial') }}" class="mt-5 grid gap-4">
                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" class="!text-slate-200" />
                                <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full rounded-xl border-white/10 bg-slate-950/40 text-white shadow-sm focus:border-emerald-400 focus:ring-emerald-400">
                                    @foreach ($academicYears as $academicYear)
                                        <option value="{{ $academicYear->id }}" @selected($selectedAcademicYear->id === $academicYear->id)>{{ $academicYear->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="period" :value="__('Period')" class="!text-slate-200" />
                                <select id="period" name="period" class="mt-1 block w-full rounded-xl border-white/10 bg-slate-950/40 text-white shadow-sm focus:border-emerald-400 focus:ring-emerald-400">
                                    <option value="month" @selected($period === 'month')>Month</option>
                                    <option value="quarter" @selected($period === 'quarter')>3 Months</option>
                                    <option value="academic_year" @selected($period === 'academic_year')>Academic Year</option>
                                </select>
                            </div>

                            <div>
                                <x-input-label for="month" :value="__('Month')" class="!text-slate-200" />
                                <select id="month" name="month" class="mt-1 block w-full rounded-xl border-white/10 bg-slate-950/40 text-white shadow-sm focus:border-emerald-400 focus:ring-emerald-400">
                                    @foreach ($monthOptions as $monthKey => $monthLabel)
                                        <option value="{{ $monthKey }}" @selected($selectedMonthKey === $monthKey)>{{ $monthLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="quarter" :value="__('3-Month Period')" class="!text-slate-200" />
                                <select id="quarter" name="quarter" class="mt-1 block w-full rounded-xl border-white/10 bg-slate-950/40 text-white shadow-sm focus:border-emerald-400 focus:ring-emerald-400">
                                    @foreach ($quarterOptions as $quarter)
                                        <option value="{{ $quarter['key'] }}" @selected($selectedQuarterKey === $quarter['key'])>{{ $quarter['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                                Apply Filter
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-5">
                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200 xl:col-span-2">
                    <div class="p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Projected Academic-Year Income</div>
                        <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ number_format($projectedAnnualIncome, 2) }}</div>
                        <div class="mt-2 text-sm text-slate-500">Based on active enrolled students and core fee structures</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Projected This Period</div>
                        <div class="mt-3 text-3xl font-semibold tracking-tight text-sky-700">{{ number_format($projectedForPeriod, 2) }}</div>
                        <div class="mt-2 text-sm text-slate-500">{{ $periodLabel }}</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Billed This Period</div>
                        <div class="mt-3 text-3xl font-semibold tracking-tight text-indigo-700">{{ number_format($billedForPeriod, 2) }}</div>
                        <div class="mt-2 text-sm text-slate-500">Net invoices issued in the selected period</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Collected This Period</div>
                        <div class="mt-3 text-3xl font-semibold tracking-tight text-emerald-700">{{ number_format($collectedForPeriod, 2) }}</div>
                        <div class="mt-2 text-sm text-slate-500">Cash + MMQR + KBZPay received</div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Outstanding This Academic Year</div>
                        <div class="mt-3 text-3xl font-semibold tracking-tight text-rose-700">{{ number_format($outstandingForAcademicYear, 2) }}</div>
                        <div class="mt-2 text-sm text-slate-500">Current unpaid balance on academic-year invoices</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Discounts This Period</div>
                        <div class="mt-3 text-3xl font-semibold tracking-tight text-amber-700">{{ number_format($discountsForPeriod, 2) }}</div>
                        <div class="mt-2 text-sm text-slate-500">Discounts on invoices issued in the selected period</div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Collection Gap</div>
                        <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ number_format($collectionGap, 2) }}</div>
                        <div class="mt-2 text-sm text-slate-500">Projected minus collected for {{ $periodLabel }}</div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <h3 class="text-lg font-semibold text-slate-900">Income Trend Chart</h3>
                        <p class="mt-1 text-sm text-slate-500">Projected vs billed vs collected across the academic year.</p>
                    </div>
                    <div class="p-6">
                        <div class="mb-4 flex flex-wrap items-center gap-4 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-sky-300"></span>Projected</span>
                            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-indigo-600"></span>Billed</span>
                            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-emerald-500"></span>Collected</span>
                        </div>

                        <div class="overflow-x-auto">
                            <div class="min-w-[720px]">
                                <div class="flex h-72 items-end gap-4">
                                    @foreach ($chartData as $point)
                                        @php
                                            $projectedHeight = $chartMax > 0 ? max(3, ($point['projected'] / $chartMax) * 100) : 0;
                                            $billedHeight = $chartMax > 0 ? max(3, ($point['billed'] / $chartMax) * 100) : 0;
                                            $collectedHeight = $chartMax > 0 ? max(3, ($point['collected'] / $chartMax) * 100) : 0;
                                        @endphp
                                        <div class="flex flex-1 flex-col items-center gap-3">
                                            <div class="flex h-60 w-full items-end justify-center gap-2 rounded-2xl bg-slate-50 px-2 py-3">
                                                <div class="w-5 rounded-t-md bg-sky-300" style="height: {{ $point['projected'] > 0 ? $projectedHeight : 0 }}%" title="Projected: {{ number_format($point['projected'], 2) }}"></div>
                                                <div class="w-5 rounded-t-md bg-indigo-600" style="height: {{ $point['billed'] > 0 ? $billedHeight : 0 }}%" title="Billed: {{ number_format($point['billed'], 2) }}"></div>
                                                <div class="w-5 rounded-t-md bg-emerald-500" style="height: {{ $point['collected'] > 0 ? $collectedHeight : 0 }}%" title="Collected: {{ number_format($point['collected'], 2) }}"></div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-xs font-semibold text-slate-700">{{ $point['label'] }}</div>
                                                <div class="mt-1 text-[11px] text-slate-500">{{ number_format($point['collected'], 0) }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h3 class="text-lg font-semibold text-slate-900">Report Notes</h3>
                            <p class="mt-1 text-sm text-slate-500">How the projected side is calculated.</p>
                        </div>
                        <div class="space-y-4 p-6 text-sm text-slate-600">
                            <p>Only active students in the selected academic year are included.</p>
                            <p>Only active, non-optional fee structures are used for projected income.</p>
                            <p>Monthly fees are spread across academic-year months, quarterly fees are spread across 3-month blocks, and installment fees follow their installment due dates when available.</p>
                            <p>Annual fees are placed at the start of the academic year, and one-time fees follow the student's admission month when possible.</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h3 class="text-lg font-semibold text-slate-900">Quick Snapshot</h3>
                            <p class="mt-1 text-sm text-slate-500">Fast finance overview for {{ $periodLabel }}.</p>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-slate-600">Projected</span>
                                <span class="text-sm font-semibold text-sky-700">{{ number_format($projectedForPeriod, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-slate-600">Billed</span>
                                <span class="text-sm font-semibold text-indigo-700">{{ number_format($billedForPeriod, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-slate-600">Collected</span>
                                <span class="text-sm font-semibold text-emerald-700">{{ number_format($collectedForPeriod, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-slate-600">Outstanding</span>
                                <span class="text-sm font-semibold text-rose-700">{{ number_format($outstandingForAcademicYear, 2) }}</span>
                            </div>
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-4 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                    <span>Collection Rate</span>
                                    <span>{{ number_format($collectionRate, 1) }}%</span>
                                </div>
                                <div class="h-2.5 rounded-full bg-slate-100">
                                    <div class="h-2.5 rounded-full bg-emerald-500" style="width: {{ min(100, $collectionRate) }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h3 class="text-lg font-semibold text-slate-900">Grade / Class Financial Summary</h3>
                    <p class="mt-1 text-sm text-slate-500">Projected and actual finance numbers grouped by enrolled grade.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Grade</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Students</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Projected</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Billed</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Collected</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($gradeSummaries as $summary)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-6 py-5 text-sm text-slate-600">
                                        <div class="font-semibold text-slate-900">{{ $summary['grade_name'] }}</div>
                                        @if ($summary['grade_group'])
                                            <div class="mt-1 text-xs text-slate-500">{{ $summary['grade_group'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-5 text-right text-sm font-semibold text-slate-900">{{ number_format($summary['students_count']) }}</td>
                                    <td class="px-6 py-5 text-right text-sm font-semibold text-sky-700">{{ number_format($summary['projected_amount'], 2) }}</td>
                                    <td class="px-6 py-5 text-right text-sm font-semibold text-indigo-700">{{ number_format($summary['billed_amount'], 2) }}</td>
                                    <td class="px-6 py-5 text-right text-sm font-semibold text-emerald-700">{{ number_format($summary['collected_amount'], 2) }}</td>
                                    <td class="px-6 py-5 text-right text-sm font-semibold text-rose-700">{{ number_format($summary['outstanding_amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">No active enrolled students found for this academic year.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
