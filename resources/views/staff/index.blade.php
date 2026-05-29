<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ __('Staff Management') }}</h2>
                <p class="mt-1 text-sm text-slate-700">Manage employees, teaching staff, departments, and optional login access.</p>
            </div>
            <a href="{{ route('staff.create') }}" class="inline-flex w-full items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600 sm:w-auto">
                Add Staff Member
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
                    @if ($staffMembers->isEmpty())
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">No staff records yet</h3>
                            <p class="mt-2 text-sm text-slate-500">Create your first staff record to begin organizing teachers, HR, and administrative access.</p>
                            <div class="mt-4">
                                <a href="{{ route('staff.create') }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Create First Staff Member</a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Staff No</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Department / Role</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Joined</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Login</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($staffMembers as $staff)
                                        <tr>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $staff->staff_no }}</td>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-900">{{ $staff->displayName() ?: '—' }}</div>
                                                <div class="text-sm text-slate-500">{{ $staff->phone ?: 'No phone' }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <div>{{ $staff->department ?: '—' }}</div>
                                                <div class="text-slate-500">{{ $staff->designation ?: '—' }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $staff->join_date?->format('d M Y') ?: '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                    @if($staff->status === 'active') bg-emerald-100 text-emerald-700
                                                    @elseif($staff->status === 'on-leave') bg-amber-100 text-amber-700
                                                    @elseif($staff->status === 'resigned') bg-rose-100 text-rose-700
                                                    @else bg-slate-200 text-slate-700 @endif">
                                                    {{ ucfirst($staff->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                @if ($staff->user)
                                                    <div class="font-medium text-slate-900">{{ '@' . $staff->user->username }}</div>
                                                    <div class="text-slate-500">{{ $staff->user->roles->first()?->name ?: 'No role' }}</div>
                                                @else
                                                    <span class="text-slate-400">No account</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('staff.edit', $staff) }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">Edit</a>
                                                    <form method="POST" action="{{ route('staff.destroy', $staff) }}" onsubmit="return confirm('Deactivate this staff member?');">
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
