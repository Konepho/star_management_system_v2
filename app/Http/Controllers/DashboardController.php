<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\ExternalExamPayment;
use App\Models\ExternalExamRegistration;
use App\Models\Grade;
use App\Models\PosProduct;
use App\Models\PosSale;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\Subject;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Support\SectionScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        /** @var User $user */
        $user = request()->user()->loadMissing('roles.permissions', 'staff.sectionAssignments');
        $today = Carbon::today();
        $currentAcademicYear = AcademicYear::query()
            ->where('is_current', true)
            ->first();

        $sectionIds = SectionScope::accessibleSectionIds($user, $currentAcademicYear?->id);

        return view('dashboard', [
            'currentAcademicYear' => $currentAcademicYear,
            'heroTitle' => $this->heroTitleFor($user),
            'heroDescription' => $this->heroDescriptionFor($user, $currentAcademicYear, $sectionIds),
            'stats' => $this->buildStats($user, $today, $currentAcademicYear?->id, $sectionIds),
            'quickActions' => $this->buildQuickActions($user),
            'attentionItems' => $this->buildAttentionItems($user, $today, $sectionIds),
            'recentActivities' => $this->buildRecentActivities($user),
        ]);
    }

    private function heroTitleFor(User $user): string
    {
        if ($user->hasRole('teacher')) {
            return 'Teaching dashboard';
        }

        if ($user->hasRole('section_head')) {
            return 'Section operations dashboard';
        }

        if ($user->hasRole('registrar_cashier')) {
            return 'Front office dashboard';
        }

        if ($user->hasRole('finance_manager')) {
            return 'Finance dashboard';
        }

        if ($user->hasRole('pos_cashier')) {
            return 'POS cashier dashboard';
        }

        if ($user->hasRole('principal') || $user->hasRole('vice_principal')) {
            return 'School overview dashboard';
        }

        return 'Operations dashboard';
    }

    private function heroDescriptionFor(User $user, ?AcademicYear $currentAcademicYear, ?Collection $sectionIds): string
    {
        $parts = [];

        if ($currentAcademicYear) {
            $parts[] = 'Current academic year: '.$currentAcademicYear->name.'.';
        }

        if ($sectionIds !== null) {
            $parts[] = $sectionIds->isEmpty()
                ? 'No section assignments are linked to your account yet.'
                : 'You are currently scoped to '.$sectionIds->count().' assigned section(s).';
        }

        if ($user->hasAnyPermission(['student_invoices.view', 'student_payments.view', 'reports.financial.view'])) {
            $parts[] = 'Use this page to watch receivables, activity, and today\'s operational priorities.';
        } elseif ($user->hasAnyPermission(['attendance.create', 'marks.create', 'student_daily_reports.create'])) {
            $parts[] = 'Use this page to jump into attendance, marks, report cards, and daily student follow-up.';
        } else {
            $parts[] = 'Use this page as your quick starting point for the modules you manage most often.';
        }

        return implode(' ', $parts);
    }

    private function buildStats(User $user, Carbon $today, ?int $currentAcademicYearId, ?Collection $sectionIds): array
    {
        $stats = [];

        if ($user->hasPermission('students.view')) {
            $studentsQuery = Student::query()->where('status', Student::STATUS_ACTIVE);

            if ($sectionIds !== null) {
                if ($sectionIds->isEmpty()) {
                    $studentsQuery->whereRaw('1 = 0');
                } else {
                    $studentsQuery->whereHas('activeEnrollments', function (Builder $query) use ($sectionIds, $currentAcademicYearId): void {
                        $query->whereIn('section_id', $sectionIds->all());

                        if ($currentAcademicYearId) {
                            $query->where('academic_year_id', $currentAcademicYearId);
                        }
                    });
                }
            }

            $stats[] = [
                'label' => 'Active Students',
                'value' => number_format($studentsQuery->count()),
                'tone' => 'sky',
            ];
        }

        if ($user->hasPermission('staff.view')) {
            $stats[] = [
                'label' => 'Active Staff',
                'value' => number_format(Staff::query()->where('status', 'active')->count()),
                'tone' => 'violet',
            ];
        }

        if ($user->hasPermission('enrollments.view')) {
            $enrollmentsQuery = Enrollment::query()->where('status', Enrollment::STATUS_ACTIVE);
            SectionScope::restrictEnrollmentQuery($enrollmentsQuery, $user, 'enrollments', $currentAcademicYearId);

            $stats[] = [
                'label' => 'Active Enrollments',
                'value' => number_format($enrollmentsQuery->count()),
                'tone' => 'emerald',
            ];
        }

        if ($user->hasPermission('student_invoices.view')) {
            $stats[] = [
                'label' => 'Open Invoices',
                'value' => number_format(StudentInvoice::query()
                    ->whereIn('status', [StudentInvoice::STATUS_ISSUED, StudentInvoice::STATUS_PARTIAL])
                    ->count()),
                'tone' => 'amber',
            ];
        }

        if ($user->hasAnyPermission(['wallets.view', 'pos_sales.view', 'pos_reports.view'])) {
            $todaysSales = PosSale::query()
                ->whereDate('created_at', $today)
                ->where('status', PosSale::STATUS_POSTED);

            $stats[] = [
                'label' => 'Today POS Sales',
                'value' => number_format((float) $todaysSales->sum('total_amount'), 2),
                'meta' => number_format($todaysSales->count()).' posted sale(s)',
                'tone' => 'rose',
            ];
        }

        if ($user->hasAnyPermission(['wallets.view', 'wallet_transactions.view', 'wallets.topup'])) {
            $todaysTopups = WalletTransaction::query()
                ->whereDate('created_at', $today)
                ->where('transaction_type', WalletTransaction::TYPE_TOPUP)
                ->where('status', WalletTransaction::STATUS_POSTED);

            $stats[] = [
                'label' => 'Today Wallet Top-ups',
                'value' => number_format((float) $todaysTopups->sum('amount'), 2),
                'meta' => number_format($todaysTopups->count()).' posted top-up(s)',
                'tone' => 'teal',
            ];
        }

        if ($user->hasPermission('report_cards.view')) {
            $stats[] = [
                'label' => 'Academic Structure',
                'value' => number_format(Grade::count()).' grades',
                'meta' => number_format(Section::count()).' sections / '.number_format(Subject::count()).' subjects',
                'tone' => 'slate',
            ];
        }

        return $stats;
    }

    private function buildQuickActions(User $user): array
    {
        $actions = [];

        $this->pushActionIfAllowed($actions, $user, 'attendance.create', 'Take Attendance', 'Record class attendance for today.', 'attendances.create', 'sky');
        $this->pushActionIfAllowed($actions, $user, 'marks.create', 'Enter Marks', 'Record or update exam marks.', 'marks.create', 'emerald');
        $this->pushActionIfAllowed($actions, $user, 'student_daily_reports.create', 'Add Daily Report', 'Write a student progress or follow-up note.', 'student-daily-reports.create', 'amber');
        $this->pushActionIfAllowed($actions, $user, 'students.create', 'Create Student', 'Register a new student profile.', 'students.create', 'sky');
        $this->pushActionIfAllowed($actions, $user, 'enrollments.create', 'Create Enrollment', 'Assign class placement and fee plan.', 'enrollments.create', 'indigo');
        $this->pushActionIfAllowed($actions, $user, 'student_invoices.create', 'Generate Invoice', 'Create a new student invoice.', 'student-invoices.create', 'violet');
        $this->pushActionIfAllowed($actions, $user, 'student_payments.collect', 'Collect Payment', 'Post a student payment or receipt.', 'student-payments.index', 'emerald');
        $this->pushActionIfAllowed($actions, $user, 'wallets.topup', 'Top Up Wallet', 'Load prepaid balance for student or staff.', 'wallet-topups.create', 'teal');
        $this->pushActionIfAllowed($actions, $user, 'pos_sales.create', 'Quick POS Sale', 'Open cashier sale screen.', 'pos-cashier.index', 'rose');
        $this->pushActionIfAllowed($actions, $user, 'report_cards.view', 'Open Report Cards', 'Review exam summaries and report cards.', 'report-cards.index', 'slate');
        $this->pushActionIfAllowed($actions, $user, 'id_cards.students.print', 'Print Student ID Cards', 'Open the student card printing page.', 'student-id-cards.index', 'amber');

        return array_slice($actions, 0, 8);
    }

    private function pushActionIfAllowed(array &$actions, User $user, string $permission, string $title, string $description, string $route, string $tone): void
    {
        if (! $user->hasPermission($permission)) {
            return;
        }

        $actions[] = [
            'title' => $title,
            'description' => $description,
            'route' => route($route),
            'tone' => $tone,
        ];
    }

    private function buildAttentionItems(User $user, Carbon $today, ?Collection $sectionIds): array
    {
        $items = [];

        if ($user->hasAnyPermission(['student_invoices.view', 'student_invoices.create', 'student_invoices.issue'])) {
            $draftInvoices = StudentInvoice::query()
                ->where('status', StudentInvoice::STATUS_DRAFT)
                ->count();

            $overdueInvoices = StudentInvoice::query()
                ->whereIn('status', [StudentInvoice::STATUS_ISSUED, StudentInvoice::STATUS_PARTIAL])
                ->whereDate('due_date', '<', $today)
                ->count();

            $items[] = [
                'label' => 'Draft invoices waiting for issue',
                'value' => $draftInvoices,
                'tone' => $draftInvoices > 0 ? 'amber' : 'slate',
            ];
            $items[] = [
                'label' => 'Overdue student invoices',
                'value' => $overdueInvoices,
                'tone' => $overdueInvoices > 0 ? 'rose' : 'slate',
            ];
        }

        if ($user->hasAnyPermission(['wallets.view', 'pos_products.manage', 'pos_reports.view'])) {
            $lowStockCount = PosProduct::query()
                ->where('status', 'active')
                ->where('stock_quantity', '<=', 5)
                ->count();

            $items[] = [
                'label' => 'Low-stock POS products',
                'value' => $lowStockCount,
                'tone' => $lowStockCount > 0 ? 'amber' : 'slate',
            ];
        }

        if ($user->hasPermission('external_exam_registrations.manage')) {
            $unpaidRegistrations = ExternalExamRegistration::query()
                ->with('postedPayments')
                ->where('status', '!=', ExternalExamRegistration::STATUS_CANCELLED)
                ->get()
                ->filter(fn (ExternalExamRegistration $registration) => $registration->balance_due > 0)
                ->count();

            $items[] = [
                'label' => 'External exam balances due',
                'value' => $unpaidRegistrations,
                'tone' => $unpaidRegistrations > 0 ? 'amber' : 'slate',
            ];
        }

        if ($user->hasPermission('students.view') && $user->hasPermission('enrollments.view')) {
            $studentsWithoutEnrollment = Student::query()
                ->where('status', Student::STATUS_ACTIVE)
                ->whereDoesntHave('activeEnrollments')
                ->count();

            if ($sectionIds === null) {
                $items[] = [
                    'label' => 'Active students without current enrollment',
                    'value' => $studentsWithoutEnrollment,
                    'tone' => $studentsWithoutEnrollment > 0 ? 'rose' : 'slate',
                ];
            }
        }

        return array_slice($items, 0, 6);
    }

    private function buildRecentActivities(User $user): Collection
    {
        $categories = $user->accessibleAuditLogCategories();

        if ($categories->isEmpty()) {
            return collect();
        }

        return AuditLog::query()
            ->with('user')
            ->whereIn('category', $categories->all())
            ->latest()
            ->limit(5)
            ->get();
    }
}
