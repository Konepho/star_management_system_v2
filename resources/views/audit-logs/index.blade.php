<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-slate-900">Audit Logs</h2>
            <p class="text-sm text-slate-600">Track finance, academic, and settings changes across the system.</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('audit-logs.index') }}" class="grid gap-4 lg:grid-cols-[2fr,1fr,1fr,auto]">
                    <div>
                        <label for="search" class="mb-1 block text-sm font-medium text-slate-700">Search</label>
                        <input
                            id="search"
                            name="search"
                            type="text"
                            value="{{ $filters['search'] }}"
                            placeholder="Summary, user, action, or module"
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                        >
                    </div>
                    <div>
                        <label for="category" class="mb-1 block text-sm font-medium text-slate-700">Category</label>
                        <select
                            id="category"
                            name="category"
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                        >
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ \Illuminate\Support\Str::headline($category) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="module" class="mb-1 block text-sm font-medium text-slate-700">Module</label>
                        <select
                            id="module"
                            name="module"
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                        >
                            <option value="">All modules</option>
                            @foreach ($modules as $module)
                                <option value="{{ $module }}" @selected($filters['module'] === $module)>{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $module)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-3">
                        <button type="submit" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                            Filter
                        </button>
                        @if ($filters['search'] !== '' || $filters['category'] !== '' || $filters['module'] !== '')
                            <a href="{{ route('audit-logs.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                Clear
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">When</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Category</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Module</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Action</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">User</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Summary</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($logs as $log)
                                <tr class="align-top">
                                    <td class="px-4 py-3 text-slate-700">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">
                                            {{ \Illuminate\Support\Str::headline($log->category) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $log->module)) }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $log->action)) }}</td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $log->user?->name ?? 'System' }}
                                        @if ($log->user?->username)
                                            <div class="text-xs text-slate-500">{{ '@' . $log->user->username }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $log->summary ?: '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($log->old_values || $log->new_values || $log->meta)
                                            <details class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                <summary class="cursor-pointer text-sm font-medium text-slate-700">View</summary>
                                                <div class="mt-3 space-y-3 text-xs text-slate-700">
                                                    @if ($log->old_values)
                                                        <div>
                                                            <div class="mb-1 font-semibold text-slate-900">Old Values</div>
                                                            <pre class="overflow-x-auto whitespace-pre-wrap rounded-lg bg-white p-3">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        </div>
                                                    @endif
                                                    @if ($log->new_values)
                                                        <div>
                                                            <div class="mb-1 font-semibold text-slate-900">New Values</div>
                                                            <pre class="overflow-x-auto whitespace-pre-wrap rounded-lg bg-white p-3">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        </div>
                                                    @endif
                                                    @if ($log->meta)
                                                        <div>
                                                            <div class="mb-1 font-semibold text-slate-900">Meta</div>
                                                            <pre class="overflow-x-auto whitespace-pre-wrap rounded-lg bg-white p-3">{{ json_encode($log->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        </div>
                                                    @endif
                                                </div>
                                            </details>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No audit logs found for the current filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div class="border-t border-slate-200 px-4 py-4">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
