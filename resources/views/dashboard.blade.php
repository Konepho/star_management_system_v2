@php
    $toneClasses = [
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'teal' => 'bg-teal-50 text-teal-700 ring-teal-200',
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-slate-900 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <p class="text-sm text-slate-500">
                {{ $currentAcademicYear?->name ? 'Academic year: '.$currentAcademicYear->name : 'No current academic year selected yet.' }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-3xl bg-gradient-to-r from-slate-950 via-sky-900 to-slate-800 text-white shadow-sm">
                <div class="grid gap-6 px-6 py-7 lg:grid-cols-[1.75fr_1fr] lg:px-8">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.28em] text-sky-100">School Operations</p>
                        <h3 class="mt-3 text-3xl font-bold tracking-tight text-white drop-shadow-sm">{{ $heroTitle }}</h3>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-200">{{ $heroDescription }}</p>
                    </div>

                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-100">Quick Snapshot</p>
                        <div class="mt-4 space-y-3 text-sm text-slate-100">
                            <div class="flex items-center justify-between gap-3">
                                <span>Current academic year</span>
                                <span class="font-semibold">{{ $currentAcademicYear?->name ?? 'Not set' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span>Visible stat cards</span>
                                <span class="font-semibold">{{ count($stats) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span>Quick actions</span>
                                <span class="font-semibold">{{ count($quickActions) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span>Recent activity items</span>
                                <span class="font-semibold">{{ $recentActivities->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @if ($stats)
                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($stats as $stat)
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                                    <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $stat['value'] }}</p>
                                    @if (! empty($stat['meta']))
                                        <p class="mt-2 text-xs text-slate-500">{{ $stat['meta'] }}</p>
                                    @endif
                                </div>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $toneClasses[$stat['tone']] ?? $toneClasses['slate'] }}">
                                    {{ ucfirst($stat['tone']) }}
                                </span>
                            </div>
                        </article>
                    @endforeach
                </section>
            @endif

            <div class="grid gap-6 xl:grid-cols-[1.35fr_1fr]">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Quick Actions</h3>
                            <p class="mt-1 text-sm text-slate-500">Open the pages you are most likely to use today.</p>
                        </div>
                    </div>

                    @if ($quickActions)
                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            @foreach ($quickActions as $action)
                                <a href="{{ $action['route'] }}" class="group rounded-2xl border border-slate-200 p-4 transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <h4 class="font-semibold text-slate-900">{{ $action['title'] }}</h4>
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $toneClasses[$action['tone']] ?? $toneClasses['slate'] }}">
                                            Open
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $action['description'] }}</p>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                            No quick actions are available for your current role yet.
                        </div>
                    @endif
                </section>

                <div class="grid gap-6">
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">Needs Attention</h3>
                        <p class="mt-1 text-sm text-slate-500">A simple operational summary of items worth checking.</p>

                        @if ($attentionItems)
                            <div class="mt-5 space-y-3">
                                @foreach ($attentionItems as $item)
                                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 px-4 py-3">
                                        <p class="text-sm text-slate-600">{{ $item['label'] }}</p>
                                        <span class="inline-flex min-w-[3rem] justify-center rounded-full px-3 py-1 text-sm font-semibold ring-1 {{ $toneClasses[$item['tone']] ?? $toneClasses['slate'] }}">
                                            {{ $item['value'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">
                                No alert items are available for your current role.
                            </div>
                        @endif
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">Recent Activity</h3>
                        <p class="mt-1 text-sm text-slate-500">Latest audit trail items you are allowed to view.</p>

                        @if ($recentActivities->isNotEmpty())
                            <div class="mt-5 space-y-4">
                                @foreach ($recentActivities as $activity)
                                    <div class="rounded-2xl border border-slate-200 px-4 py-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900">{{ $activity->summary }}</p>
                                                <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">
                                                    {{ $activity->category }} / {{ $activity->module }}
                                                </p>
                                            </div>
                                            <span class="text-xs text-slate-400">{{ $activity->created_at?->diffForHumans() }}</span>
                                        </div>
                                        <p class="mt-2 text-xs text-slate-500">
                                            {{ $activity->user?->name ?? 'System' }} • {{ ucfirst(str_replace('_', ' ', $activity->action)) }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">
                                No recent activity is visible for your current audit-log permissions.
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
