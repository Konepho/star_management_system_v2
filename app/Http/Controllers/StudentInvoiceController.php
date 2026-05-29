<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentInvoiceRequest;
use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\DiscountDefinition;
use App\Models\Enrollment;
use App\Models\FeeItem;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Services\AuditLogService;
use App\Services\DocumentNumberService;
use App\Services\InvoicePaymentTimingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentInvoiceController extends Controller
{
    public function __construct(
        protected InvoicePaymentTimingService $invoicePaymentTimingService,
        protected DocumentNumberService $documentNumberService,
    ) {
    }

    public function index(): View
    {
        $search = trim((string) request('search', ''));

        $invoicesQuery = StudentInvoice::query()
            ->with(['student', 'academicYear', 'grade', 'section', 'payments', 'items', 'discounts.discountDefinition', 'feePlan', 'enrollment']);

        if ($search !== '') {
            $invoicesQuery->where(function ($query) use ($search) {
                $query->where('invoice_no', 'like', '%' . $search . '%')
                    ->orWhere('billing_year_label', 'like', '%' . $search . '%')
                    ->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery
                            ->where('admission_no', 'like', '%' . $search . '%')
                            ->orWhere('name_en', 'like', '%' . $search . '%')
                            ->orWhere('name_mm', 'like', '%' . $search . '%')
                            ->orWhere('preferred_name', 'like', '%' . $search . '%')
                            ->orWhere('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%');
                    });
            });
        }

        return view('student-invoices.index', [
            'invoices' => $invoicesQuery
                ->orderByDesc('issue_date')
                ->orderByDesc('id')
                ->get(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $students = Student::query()
            ->with([
                'enrollments' => fn ($query) => $query
                    ->with(['academicYear', 'grade', 'section', 'feePlan.feeStructures'])
                    ->where('status', Enrollment::STATUS_ACTIVE)
                    ->orderByDesc('enrollment_date')
                    ->orderByDesc('id'),
            ])
            ->orderBy('name_en')
            ->orderBy('first_name')
            ->get();

        $enrollmentSummaries = $students
            ->flatMap(function (Student $student) {
                return $student->enrollments->mapWithKeys(function (Enrollment $enrollment) use ($student) {
                    $feeStructuresCount = $enrollment->feePlan
                        ? $enrollment->feePlan->feeStructures->where('status', 'active')->count()
                        : 0;

                    return [
                        $student->id . ':' . $enrollment->academic_year_id => [
                            'student_label' => $student->full_name . ' (' . $student->admission_no . ')',
                            'academic_year' => $enrollment->academicYear?->name,
                            'grade' => $enrollment->grade?->name,
                            'section' => $enrollment->section?->name,
                            'fee_plan' => $enrollment->feePlan?->name,
                            'fee_structures_count' => $feeStructuresCount,
                            'has_fee_plan' => (bool) $enrollment->feePlan,
                        ],
                    ];
                });
            })
            ->all();

        return view('student-invoices.create', [
            'students' => $students,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'enrollmentSummaries' => $enrollmentSummaries,
            'feeItems' => FeeItem::query()
                ->with('feeCategory')
                ->where('status', 'active')
                ->orderBy('name')
                ->orderBy('variant')
                ->get(),
            'invoice' => new StudentInvoice([
                'issue_date' => now()->toDateString(),
                'status' => 'issued',
            ]),
        ]);
    }

    public function store(StoreStudentInvoiceRequest $request): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $student = Student::query()->findOrFail($request->validated('student_id'));
        $enrollment = $this->activeEnrollmentForBilling($student, (int) $request->validated('academic_year_id'));
        abort_unless($enrollment, 422, 'Active enrollment required for invoice generation.');

        $feeStructures = $this->applicableFeeStructuresForEnrollment($enrollment)
            ->values();
        $feeItems = FeeItem::query()
            ->with('feeCategory')
            ->whereIn('id', $request->validated('fee_item_ids', []))
            ->where('status', 'active')
            ->get()
            ->values();

        $invoice = DB::transaction(function () use ($request, $student, $feeStructures, $feeItems, $enrollment) {
                $invoice = StudentInvoice::create([
                'invoice_no' => $this->documentNumberService->nextInvoiceNumber($enrollment->academicYear),
                'student_id' => $student->id,
                'academic_year_id' => $request->validated('academic_year_id'),
                'enrollment_id' => $enrollment->id,
                'fee_plan_id' => $enrollment->fee_plan_id,
                'grade_id' => $enrollment->grade_id,
                'section_id' => $enrollment->section_id,
                'issue_date' => $request->validated('issue_date'),
                'due_date' => $request->validated('due_date'),
                'status' => $request->validated('status'),
                'billing_period_type' => $request->validated('billing_period_type'),
                'billing_month' => $request->validated('billing_month'),
                'billing_quarter' => $request->validated('billing_quarter'),
                'billing_year_label' => $request->validated('billing_year_label'),
                'issued_at' => $request->validated('status') === StudentInvoice::STATUS_ISSUED ? now() : null,
                'notes' => $request->validated('notes'),
                'total_amount' => 0,
            ]);

            $items = [];
            foreach ($feeStructures as $feeStructure) {
                if ($feeStructure->billing_cycle === 'installment') {
                    foreach ($feeStructure->installments as $installment) {
                        $items[] = [
                            'fee_structure_id' => $feeStructure->id,
                            'fee_category_id' => $feeStructure->fee_category_id,
                            'description' => $feeStructure->feeCategory?->name . ' - Installment ' . $installment->installment_no,
                            'billing_cycle' => $feeStructure->billing_cycle,
                            'installment_no' => $installment->installment_no,
                            'quantity' => 1,
                            'unit_price' => $installment->amount,
                            'amount' => $installment->amount,
                            'due_date' => $installment->due_date,
                            'remarks' => $installment->remarks,
                        ];
                    }

                    continue;
                }

                $items[] = [
                    'fee_structure_id' => $feeStructure->id,
                    'fee_category_id' => $feeStructure->fee_category_id,
                    'description' => $feeStructure->feeCategory?->name,
                    'billing_cycle' => $feeStructure->billing_cycle,
                    'installment_no' => null,
                    'quantity' => 1,
                    'unit_price' => $feeStructure->amount,
                    'amount' => $feeStructure->amount,
                    'due_date' => $request->validated('due_date'),
                    'remarks' => $feeStructure->remarks,
                ];
            }

            foreach ($feeItems as $feeItem) {
                $quantity = max(1, (int) ($request->input("fee_item_quantities.{$feeItem->id}") ?: 1));
                $lineAmount = $quantity * (float) $feeItem->price;

                $items[] = [
                    'fee_structure_id' => null,
                    'fee_item_id' => $feeItem->id,
                    'fee_category_id' => $feeItem->fee_category_id,
                    'description' => $feeItem->name . ($feeItem->variant ? ' - ' . $feeItem->variant : ''),
                    'billing_cycle' => 'one-time',
                    'installment_no' => null,
                    'quantity' => $quantity,
                    'unit_price' => $feeItem->price,
                    'amount' => $lineAmount,
                    'due_date' => $request->validated('due_date'),
                    'remarks' => $feeItem->description,
                ];
            }

            $invoice->items()->createMany($items);
            $invoice->load(['student.discounts.discountDefinition', 'items.feeCategory']);
            $this->invoicePaymentTimingService->resetIfNoPaymentsRemain($invoice);
            $invoice->load(['items', 'discounts', 'payments']);
            $invoice->recalculateTotals();
            $invoice->refreshPaymentStatus();

            return $invoice;
        });

        $auditLogService->log(
            'finance',
            'student_invoices',
            'created',
            $invoice->fresh(),
            [],
            $auditLogService->modelState($invoice->fresh()),
            'Created student invoice ' . $invoice->invoice_no . '.',
            [
                'student_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'fee_structures_count' => $feeStructures->count(),
                'fee_items_count' => $feeItems->count(),
            ],
        );

        return redirect()
            ->route('student-invoices.show', $invoice)
            ->with('status', 'Student invoice generated successfully.');
    }

    public function show(StudentInvoice $studentInvoice): View
    {
        return view('student-invoices.show', [
            'invoice' => $studentInvoice->load(['student', 'academicYear', 'grade', 'section', 'feePlan', 'enrollment', 'items.feeCategory', 'items.feeItem', 'items.discounts.discountDefinition', 'payments', 'discounts.item', 'discounts.discountDefinition']),
            'discountDefinitions' => DiscountDefinition::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function print(StudentInvoice $studentInvoice): View
    {
        $invoice = $studentInvoice->load([
            'student',
            'academicYear',
            'grade',
            'section',
            'feePlan',
            'enrollment',
            'items.feeCategory',
            'items.feeItem',
            'items.discounts.discountDefinition',
            'payments',
            'discounts.item',
            'discounts.discountDefinition',
        ]);

        $nameFormat = AppSetting::getValue('invoice.student_name_format', 'preferred_then_english');

        return view('student-invoices.print', [
            'invoice' => $invoice,
            'invoiceSettings' => [
                'school_name' => AppSetting::getValue('invoice.school_name', 'STAR School'),
                'school_phone' => AppSetting::getValue('invoice.school_phone', ''),
                'school_email' => AppSetting::getValue('invoice.school_email', ''),
                'school_address' => AppSetting::getValue('invoice.school_address', ''),
                'school_logo_data_url' => $this->logoDataUrl(AppSetting::getValue('invoice.school_logo_path')),
                'student_display_name' => $this->formattedStudentName($invoice->student, $nameFormat),
            ],
        ]);
    }

    public function updateStatus(StudentInvoice $studentInvoice): RedirectResponse
    {
        $action = request()->string('action')->value();
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($studentInvoice);

        if (! $this->canManageInvoiceAction($action)) {
            abort(403);
        }

        if ($action === 'issue') {
            if ($studentInvoice->status !== StudentInvoice::STATUS_DRAFT) {
                return back()->withErrors(['invoice' => 'Only draft invoices can be issued.']);
            }

            $studentInvoice->forceFill([
                'status' => StudentInvoice::STATUS_ISSUED,
                'issued_at' => now(),
            ])->save();

            $auditLogService->log(
                'finance',
                'student_invoices',
                'issued',
                $studentInvoice->fresh(),
                $beforeState,
                $auditLogService->modelState($studentInvoice->fresh()),
                'Issued student invoice ' . $studentInvoice->invoice_no . '.',
            );

            return redirect()
                ->route('student-invoices.show', $studentInvoice)
                ->with('status', 'Invoice issued successfully.');
        }

        if ($action === 'cancel') {
            if ($studentInvoice->postedPayments()->exists()) {
                return back()->withErrors(['invoice' => 'Invoices with payments cannot be cancelled.']);
            }

            if ($studentInvoice->status !== StudentInvoice::STATUS_DRAFT) {
                return back()->withErrors(['invoice' => 'Only draft invoices can be cancelled.']);
            }

            $studentInvoice->forceFill([
                'status' => StudentInvoice::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ])->save();

            $auditLogService->log(
                'finance',
                'student_invoices',
                'cancelled',
                $studentInvoice->fresh(),
                $beforeState,
                $auditLogService->modelState($studentInvoice->fresh()),
                'Cancelled student invoice ' . $studentInvoice->invoice_no . '.',
            );

            return redirect()
                ->route('student-invoices.show', $studentInvoice)
                ->with('status', 'Invoice cancelled successfully.');
        }

        if ($action === 'void') {
            if ($studentInvoice->postedPayments()->exists()) {
                return back()->withErrors(['invoice' => 'Invoices with payments cannot be voided.']);
            }

            if (! in_array($studentInvoice->status, [StudentInvoice::STATUS_ISSUED], true)) {
                return back()->withErrors(['invoice' => 'Only issued invoices can be voided.']);
            }

            $studentInvoice->forceFill([
                'status' => StudentInvoice::STATUS_VOID,
                'voided_at' => now(),
            ])->save();

            $auditLogService->log(
                'finance',
                'student_invoices',
                'voided',
                $studentInvoice->fresh(),
                $beforeState,
                $auditLogService->modelState($studentInvoice->fresh()),
                'Voided student invoice ' . $studentInvoice->invoice_no . '.',
            );

            return redirect()
                ->route('student-invoices.show', $studentInvoice)
                ->with('status', 'Invoice voided successfully.');
        }

        return redirect()
            ->route('student-invoices.show', $studentInvoice)
            ->withErrors(['invoice' => 'Unsupported invoice action.']);
    }

    public function preview(Student $student): View
    {
        $student->load(['enrollments' => fn ($query) => $query->with(['academicYear', 'grade', 'section', 'feePlan'])->orderByDesc('enrollment_date')->orderByDesc('id')]);
        $activeEnrollment = $student->enrollments->firstWhere('status', Enrollment::STATUS_ACTIVE);

        return view('student-invoices.preview', [
            'student' => $student,
            'activeEnrollment' => $activeEnrollment,
            'feeStructures' => $activeEnrollment
                ? $this->applicableFeeStructuresForEnrollment($activeEnrollment)
                : collect(),
        ]);
    }

    protected function activeEnrollmentForBilling(Student $student, int $academicYearId): ?Enrollment
    {
        return Enrollment::query()
            ->with(['feePlan.feeStructures.feeCategory', 'feePlan.feeStructures.installments', 'grade'])
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->first();
    }

    protected function applicableFeeStructuresForEnrollment(Enrollment $enrollment)
    {
        if (! $enrollment->feePlan) {
            return collect();
        }

        return $enrollment->feePlan->feeStructures
            ->where('status', 'active')
            ->values();
    }

    protected function logoDataUrl(?string $logoPath): ?string
    {
        if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $fileContents = Storage::disk('public')->get($logoPath);
        $mimeType = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';

        return 'data:' . $mimeType . ';base64,' . base64_encode($fileContents);
    }

    protected function formattedStudentName(?Student $student, string $format): string
    {
        if (! $student) {
            return '—';
        }

        $englishName = trim((string) ($student->name_en ?: $student->full_name));
        $burmeseName = trim((string) $student->name_mm);
        $preferredName = trim((string) $student->preferred_name);

        return match ($format) {
            'english_only' => $englishName !== '' ? $englishName : '—',
            'bilingual' => trim(collect([$englishName, $burmeseName])->filter()->implode(' / ')) ?: '—',
            default => trim(collect([$preferredName, $englishName, $burmeseName])->filter()->unique()->implode(' / ')) ?: '—',
        };
    }

    protected function canManageInvoiceAction(?string $action): bool
    {
        $user = Auth::user();

        if (! $user || ! is_string($action) || $action === '') {
            return false;
        }

        return match ($action) {
            'issue', 'cancel' => $user->hasPermission('student_invoices.issue'),
            'void' => $user->hasPermission('student_invoices.void'),
            default => false,
        };
    }

}
