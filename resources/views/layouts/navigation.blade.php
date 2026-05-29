@php
    $sidebarSections = [
        [
            'title' => 'Overview',
            'items' => [
                ['label' => 'Dashboard', 'route' => route('dashboard'), 'active' => request()->routeIs('dashboard'), 'permissions' => ['dashboard.view']],
                ['label' => 'Admin Settings', 'route' => route('admin-settings.edit'), 'active' => request()->routeIs('admin-settings.*'), 'permissions' => ['admin_settings.manage']],
                ['label' => 'Audit Logs', 'route' => route('audit-logs.index'), 'active' => request()->routeIs('audit-logs.*'), 'permissions' => ['audit_logs.finance.view', 'audit_logs.academic.view', 'audit_logs.settings.view']],
            ],
        ],
        [
            'title' => 'School Setup',
            'items' => [
                ['label' => 'Academic Years', 'route' => route('academic-years.index'), 'active' => request()->routeIs('academic-years.*'), 'permissions' => ['academic_years.manage']],
                ['label' => 'Grades', 'route' => route('grades.index'), 'active' => request()->routeIs('grades.*'), 'permissions' => ['grades.manage']],
                ['label' => 'Rooms', 'route' => route('rooms.index'), 'active' => request()->routeIs('rooms.*'), 'permissions' => ['rooms.manage']],
                ['label' => 'Sections', 'route' => route('sections.index'), 'active' => request()->routeIs('sections.*'), 'permissions' => ['sections.manage']],
                ['label' => 'Enrollments', 'route' => route('enrollments.index'), 'active' => request()->routeIs('enrollments.*'), 'permissions' => ['enrollments.view', 'enrollments.create', 'enrollments.update', 'enrollments.delete']],
                ['label' => 'Subjects', 'route' => route('subjects.index'), 'active' => request()->routeIs('subjects.*'), 'permissions' => ['subjects.manage']],
            ],
        ],
        [
            'title' => 'Finance Setup',
            'items' => [
                ['label' => 'Fee Categories', 'route' => route('fee-categories.index'), 'active' => request()->routeIs('fee-categories.*'), 'permissions' => ['fee_categories.manage']],
                ['label' => 'Discount Definitions', 'route' => route('discount-definitions.index'), 'active' => request()->routeIs('discount-definitions.*'), 'permissions' => ['discount_definitions.manage']],
                ['label' => 'Student Discounts', 'route' => route('student-discounts.index'), 'active' => request()->routeIs('student-discounts.*'), 'permissions' => ['student_discounts.manage']],
                ['label' => 'Fee Items', 'route' => route('fee-items.index'), 'active' => request()->routeIs('fee-items.*'), 'permissions' => ['fee_items.manage']],
                ['label' => 'Fee Plans', 'route' => route('fee-plans.index'), 'active' => request()->routeIs('fee-plans.*'), 'permissions' => ['fee_plans.manage']],
                ['label' => 'Fee Structures', 'route' => route('fee-structures.index'), 'active' => request()->routeIs('fee-structures.*'), 'permissions' => ['fee_structures.manage']],
                ['label' => 'Student Invoices', 'route' => route('student-invoices.index'), 'active' => request()->routeIs('student-invoices.*'), 'permissions' => ['student_invoices.view', 'student_invoices.create', 'student_invoices.print', 'student_invoices.issue', 'student_invoices.void']],
                ['label' => 'Payment Collection', 'route' => route('student-payments.index'), 'active' => request()->routeIs('student-payments.*'), 'permissions' => ['student_payments.view', 'student_payments.collect', 'student_payments.delete']],
                ['label' => 'Financial Report', 'route' => route('reports.financial'), 'active' => request()->routeIs('reports.financial'), 'permissions' => ['reports.financial.view']],
                ['label' => 'Receivables Report', 'route' => route('reports.receivables'), 'active' => request()->routeIs('reports.receivables'), 'permissions' => ['reports.receivables.view']],
            ],
        ],
        [
            'title' => 'POS / Wallet',
            'items' => [
                ['label' => 'POS Cashier', 'route' => route('pos-cashier.index'), 'active' => request()->routeIs('pos-cashier.*'), 'permissions' => ['wallets.topup', 'pos_sales.create']],
                ['label' => 'Wallets', 'route' => route('wallets.index'), 'active' => request()->routeIs('wallets.*'), 'permissions' => ['wallets.view']],
                ['label' => 'Wallet Top-up', 'route' => route('wallet-topups.create'), 'active' => request()->routeIs('wallet-topups.*'), 'permissions' => ['wallets.topup']],
                ['label' => 'POS Categories', 'route' => route('pos-product-categories.index'), 'active' => request()->routeIs('pos-product-categories.*'), 'permissions' => ['pos_products.manage']],
                ['label' => 'POS Products', 'route' => route('pos-products.index'), 'active' => request()->routeIs('pos-products.*'), 'permissions' => ['pos_products.manage']],
                ['label' => 'POS Sales', 'route' => route('pos-sales.index'), 'active' => request()->routeIs('pos-sales.*'), 'permissions' => ['pos_sales.view', 'pos_sales.create', 'pos_sales.reverse']],
                ['label' => 'POS Reports', 'route' => route('reports.pos'), 'active' => request()->routeIs('reports.pos'), 'permissions' => ['pos_reports.view']],
            ],
        ],
        [
            'title' => 'Academic Flow',
            'items' => [
                ['label' => 'Students', 'route' => route('students.index'), 'active' => request()->routeIs('students.*'), 'permissions' => ['students.view', 'students.create', 'students.update', 'students.delete']],
                ['label' => 'ID Cards', 'route' => route('student-id-cards.index'), 'active' => request()->routeIs('student-id-cards.*'), 'permissions' => ['id_cards.students.print', 'id_cards.staff.print']],
                ['label' => 'Staff Management', 'route' => route('staff.index'), 'active' => request()->routeIs('staff.*'), 'permissions' => ['staff.view', 'staff.create', 'staff.update', 'staff.delete']],
                ['label' => 'Exams', 'route' => route('exams.index'), 'active' => request()->routeIs('exams.*'), 'permissions' => ['exams.manage']],
                ['label' => 'External Exam Sessions', 'route' => route('external-exam-sessions.index'), 'active' => request()->routeIs('external-exam-sessions.*'), 'permissions' => ['external_exam_sessions.manage']],
                ['label' => 'External Exam Registrations', 'route' => route('external-exam-registrations.index'), 'active' => request()->routeIs('external-exam-registrations.*'), 'permissions' => ['external_exam_registrations.manage']],
                ['label' => 'External Exam Payments', 'route' => route('external-exam-payments.index'), 'active' => request()->routeIs('external-exam-payments.*'), 'permissions' => ['external_exam_payments.view', 'external_exam_payments.collect', 'external_exam_payments.delete']],
                ['label' => 'Marks', 'route' => route('marks.index'), 'active' => request()->routeIs('marks.*'), 'permissions' => ['marks.view', 'marks.create', 'marks.update', 'marks.delete']],
                ['label' => 'Attendance', 'route' => route('attendances.index'), 'active' => request()->routeIs('attendances.*'), 'permissions' => ['attendance.view', 'attendance.create', 'attendance.update']],
                ['label' => 'Student Daily Reports', 'route' => route('student-daily-reports.index'), 'active' => request()->routeIs('student-daily-reports.*'), 'permissions' => ['student_daily_reports.view', 'student_daily_reports.create', 'student_daily_reports.update', 'student_daily_reports.delete']],
                ['label' => 'Report Cards', 'route' => route('report-cards.index'), 'active' => request()->routeIs('report-cards.*'), 'permissions' => ['report_cards.view', 'report_cards.print']],
            ],
        ],
        [
            'title' => 'Planned Modules',
            'items' => [
                ['label' => 'Library Management', 'coming_soon' => true],
                ['label' => 'Class Timetable', 'coming_soon' => true],
                ['label' => 'Notifications', 'coming_soon' => true],
            ],
        ],
    ];

    $sidebarSections = collect($sidebarSections)
        ->map(function (array $section) {
            $items = collect($section['items'])
                ->filter(function (array $item) {
                    if (! empty($item['coming_soon'])) {
                        return true;
                    }

                    $permissions = $item['permissions'] ?? [];

                    return $permissions === [] || Auth::user()?->hasAnyPermission($permissions);
                })
                ->values()
                ->all();

            return [
                'title' => $section['title'],
                'items' => $items,
            ];
        })
        ->filter(fn (array $section) => $section['items'] !== [])
        ->values()
        ->all();
@endphp

<nav x-data="{ open: false }" class="border-b border-slate-200 bg-white sm:w-80 sm:flex-shrink-0 sm:border-b-0 sm:border-r sm:shadow-sm">
    <div class="flex items-center justify-between px-4 py-4 sm:hidden">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <x-application-logo class="block h-9 w-auto fill-current text-slate-800" />
            <div>
                <div class="text-sm font-semibold text-slate-900">STAR School</div>
                <div class="text-xs text-slate-600">Management System</div>
            </div>
        </a>

        <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus:outline-none">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div class="hidden h-screen flex-col sm:sticky sm:top-0 sm:flex">
        <div class="border-b border-slate-200 px-6 py-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <x-application-logo class="block h-10 w-auto fill-current text-slate-800" />
                <div>
                    <div class="text-base font-semibold text-slate-900">STAR School</div>
                    <div class="text-sm text-slate-600">Management System</div>
                </div>
            </a>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-6">
            <div class="space-y-6">
                @foreach ($sidebarSections as $section)
                    <div>
                        <div class="mb-3 px-4 text-xs font-bold uppercase tracking-[0.2em] text-slate-600">{{ $section['title'] }}</div>
                        <div class="space-y-2">
                            @foreach ($section['items'] as $item)
                                @if (! empty($item['coming_soon']))
                                    <div class="flex items-center justify-between rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                                        <span>{{ $item['label'] }}</span>
                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-700">Soon</span>
                                    </div>
                                @else
                                    <x-nav-link :href="$item['route']" :active="$item['active']">
                                        <span class="flex w-full items-center justify-between gap-3">
                                            <span>{{ $item['label'] }}</span>
                                            @if ($item['active'])
                                                <span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>
                                            @endif
                                        </span>
                                    </x-nav-link>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="border-t border-slate-200 px-4 py-4">
            <div class="rounded-2xl bg-slate-50 p-4">
                <div class="text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</div>
                <div class="text-sm text-slate-600">{{ '@' . Auth::user()->username }}</div>
                <div class="mt-4 space-y-2">
                    <a href="{{ route('profile.edit') }}" class="flex items-center rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-white hover:text-slate-950">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center rounded-xl px-4 py-3 text-sm font-semibold text-rose-700 transition hover:bg-white hover:text-rose-800">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-slate-200 bg-white sm:hidden">
        <div class="space-y-4 px-3 py-3">
            @foreach ($sidebarSections as $section)
                <div>
                    <div class="px-3 pb-2 text-[11px] font-bold uppercase tracking-[0.2em] text-slate-600">{{ $section['title'] }}</div>
                    <div class="space-y-1">
                        @foreach ($section['items'] as $item)
                            @if (! empty($item['coming_soon']))
                                <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700">
                                    <span>{{ $item['label'] }}</span>
                                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-700">Soon</span>
                                </div>
                            @else
                                <x-responsive-nav-link :href="$item['route']" :active="$item['active']">
                                    {{ $item['label'] }}
                                </x-responsive-nav-link>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="border-t border-slate-200 px-4 py-4">
            <div class="font-medium text-base text-slate-800">{{ Auth::user()->name }}</div>
            <div class="font-medium text-sm text-slate-600">{{ '@' . Auth::user()->username }}</div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
