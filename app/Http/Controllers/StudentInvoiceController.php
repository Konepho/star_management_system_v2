<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentInvoiceRequest;
use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\DiscountDefinition;
use App\Models\Enrollment;
use App\Models\FeeItem;
use App\Models\Student;
use App\Models\StudentExtraClassRegistration;
use App\Models\StudentInvoice;
use App\Models\StudentInvoiceItem;
use App\Services\AuditLogService;
use App\Services\DocumentNumberService;
use App\Services\InvoicePaymentTimingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $selectedStudentId = old('student_id');
        $selectedAcademicYearId = old('academic_year_id');
        $selectedStudent = $selectedStudentId
            ? Student::query()
                ->with([
                    'enrollments' => fn ($query) => $query
                        ->with(['academicYear', 'grade', 'section', 'feePlan'])
                        ->where('status', Enrollment::STATUS_ACTIVE)
                        ->orderByDesc('enrollment_date')
                        ->orderByDesc('id'),
                ])
                ->find($selectedStudentId)
            : null;
        $feeItems = FeeItem::query()
            ->with('feeCategory')
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('variant')
            ->get();

        return view('student-invoices.create', [
            'studentOptions' => $selectedStudent ? [$this->studentOption($selectedStudent)] : [],
            'initialStudentLabel' => $selectedStudent ? $this->studentOption($selectedStudent)['label'] : '',
            'initialEnrollmentSummary' => $selectedStudent && $selectedAcademicYearId
                ? $this->buildEnrollmentSummary($selectedStudent, (int) $selectedAcademicYearId)
                : null,
            'studentSearchUrl' => route('student-invoices.student-search'),
            'studentSummaryUrl' => route('student-invoices.summary'),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'feeItems' => $feeItems,
            'feeItemOptions' => $feeItems
                ->map(fn (FeeItem $feeItem) => [
                    'id' => $feeItem->id,
                    'price' => (float) $feeItem->price,
                    'label' => $feeItem->name . ($feeItem->variant ? ' - ' . $feeItem->variant : ''),
                    'fee_category' => $feeItem->feeCategory?->name,
                    'discount_policy_label' => $feeItem->discount_policy_label,
                ])
                ->values(),
            'invoice' => new StudentInvoice([
                'issue_date' => now()->toDateString(),
                'status' => 'issued',
            ]),
        ]);
    }

    public function studentSearch(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $students = Student::query()
            ->with([
                'enrollments' => fn ($query) => $query
                    ->with(['academicYear', 'grade', 'feePlan'])
                    ->where('status', Enrollment::STATUS_ACTIVE)
                    ->orderByDesc('enrollment_date')
                    ->orderByDesc('id'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($studentQuery) use ($search) {
                    $studentQuery
                        ->where('admission_no', 'like', '%' . $search . '%')
                        ->orWhere('name_en', 'like', '%' . $search . '%')
                        ->orWhere('name_mm', 'like', '%' . $search . '%')
                        ->orWhere('preferred_name', 'like', '%' . $search . '%')
                        ->orWhere('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name_en')
            ->orderBy('first_name')
            ->limit(12)
            ->get();

        return response()->json([
            'data' => $students->map(fn (Student $student) => $this->studentOption($student))->values()->all(),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
        ]);

        $student = Student::query()->findOrFail((int) $validated['student_id']);

        return response()->json([
            'data' => $this->buildEnrollmentSummary($student, (int) $validated['academic_year_id']),
        ]);
    }

    public function store(StoreStudentInvoiceRequest $request): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $student = Student::query()->findOrFail($request->validated('student_id'));
        $academicYear = AcademicYear::query()->findOrFail((int) $request->validated('academic_year_id'));
        $enrollment = $this->activeEnrollmentForBilling($student, (int) $request->validated('academic_year_id'));

        $academicChargeItems = $enrollment
            ? $this->feePlanPreviewItems($this->applicableFeeStructuresForEnrollment($enrollment))->values()
            : collect();
        $excludedAcademicChargeKeys = collect($request->validated('excluded_academic_charge_keys', []))
            ->values();
        $excludedAcademicChargeReasons = collect($request->validated('excluded_academic_charge_reasons', []));
        $excludedAcademicCharges = $academicChargeItems
            ->whereIn('key', $excludedAcademicChargeKeys)
            ->map(function (array $item) use ($excludedAcademicChargeReasons) {
                return [
                    'key' => $item['key'],
                    'description' => $item['description'],
                    'amount' => (float) $item['amount'],
                    'reason' => trim((string) $excludedAcademicChargeReasons->get($item['key'], '')),
                ];
            })
            ->values();
        $includedAcademicChargeItems = $academicChargeItems
            ->reject(fn (array $item) => $excludedAcademicChargeKeys->contains($item['key']))
            ->values();
        $feeItems = FeeItem::query()
            ->with('feeCategory')
            ->whereIn('id', $request->validated('fee_item_ids', []))
            ->where('status', 'active')
            ->get()
            ->values();
        $extraRegistrations = StudentExtraClassRegistration::query()
            ->with(['offering.feeCategory'])
            ->whereIn('id', $request->validated('extra_registration_ids', []))
            ->get()
            ->values();
        $invoiceStream = StudentInvoice::detectInvoiceStream(
            $includedAcademicChargeItems->count(),
            $extraRegistrations->count(),
            $feeItems->count(),
        );

        $invoice = DB::transaction(function () use ($request, $student, $academicYear, $includedAcademicChargeItems, $excludedAcademicCharges, $feeItems, $extraRegistrations, $enrollment, $invoiceStream) {
                $invoiceNotes = trim((string) ($request->validated('notes') ?? ''));
                $internalInvoiceNotes = trim((string) ($request->validated('internal_notes') ?? ''));

                if ($excludedAcademicCharges->isNotEmpty()) {
                    $exclusionNote = 'Academic fee exclusions: ' . $excludedAcademicCharges
                        ->map(fn (array $item) => $item['description'] . ($item['reason'] !== '' ? ' (' . $item['reason'] . ')' : ''))
                        ->implode('; ');

                    $internalInvoiceNotes = trim($internalInvoiceNotes !== '' ? $internalInvoiceNotes . PHP_EOL . $exclusionNote : $exclusionNote);
                }

                $invoice = StudentInvoice::create([
                'invoice_no' => $this->documentNumberService->nextInvoiceNumber($academicYear),
                'student_id' => $student->id,
                'academic_year_id' => $request->validated('academic_year_id'),
                'enrollment_id' => $enrollment?->id,
                'fee_plan_id' => $enrollment?->fee_plan_id,
                'grade_id' => $enrollment?->grade_id,
                'section_id' => $enrollment?->section_id,
                'issue_date' => $request->validated('issue_date'),
                'due_date' => $request->validated('due_date'),
                'status' => $request->validated('status'),
                'billing_period_type' => $request->validated('billing_period_type'),
                'invoice_stream' => $invoiceStream,
                'billing_month' => $request->validated('billing_month'),
                'billing_quarter' => $request->validated('billing_quarter'),
                'billing_year_label' => $request->validated('billing_year_label'),
                'issued_at' => $request->validated('status') === StudentInvoice::STATUS_ISSUED ? now() : null,
                'notes' => $invoiceNotes !== '' ? $invoiceNotes : null,
                'internal_notes' => $internalInvoiceNotes !== '' ? $internalInvoiceNotes : null,
                'total_amount' => 0,
            ]);

            $items = $includedAcademicChargeItems
                ->map(fn (array $item) => [
                    'fee_structure_id' => $item['fee_structure_id'],
                    'fee_category_id' => $item['fee_category_id'],
                    'description' => $item['description'],
                    'billing_cycle' => $item['billing_cycle'],
                    'installment_no' => $item['installment_no'],
                    'quantity' => 1,
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['amount'],
                    'due_date' => $item['due_date'] ?: $request->validated('due_date'),
                    'remarks' => $item['remarks'],
                ])
                ->values()
                ->all();

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

            foreach ($extraRegistrations as $registration) {
                $offering = $registration->offering;
                $description = $offering?->display_name ?: 'Extra Class';

                $items[] = [
                    'fee_structure_id' => null,
                    'fee_item_id' => null,
                    'extra_class_offering_id' => $offering?->id,
                    'student_extra_class_registration_id' => $registration->id,
                    'fee_category_id' => $offering?->fee_category_id,
                    'description' => $description,
                    'billing_cycle' => 'one-time',
                    'installment_no' => null,
                    'quantity' => 1,
                    'unit_price' => (float) ($offering?->fee_amount ?? 0),
                    'amount' => (float) ($offering?->fee_amount ?? 0),
                    'due_date' => $request->validated('due_date'),
                    'remarks' => $registration->remarks,
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
                'enrollment_id' => $enrollment?->id,
                'invoice_stream' => $invoice->invoice_stream,
                'fee_structures_count' => $includedAcademicChargeItems->count(),
                'fee_items_count' => $feeItems->count(),
                'extra_class_registrations_count' => $extraRegistrations->count(),
                'excluded_academic_charges' => $excludedAcademicCharges->all(),
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

    protected function feePlanPreviewItems($feeStructures)
    {
        return collect($feeStructures)->flatMap(function ($feeStructure) {
            if ($feeStructure->billing_cycle === 'installment') {
                return $feeStructure->installments->map(fn ($installment) => [
                    'key' => $this->academicChargeKey($feeStructure->id, $installment->installment_no),
                    'fee_structure_id' => $feeStructure->id,
                    'fee_category_id' => $feeStructure->fee_category_id,
                    'description' => $feeStructure->feeCategory?->name . ' - Installment ' . $installment->installment_no,
                    'installment_no' => $installment->installment_no,
                    'unit_price' => (float) $installment->amount,
                    'amount' => (float) $installment->amount,
                    'billing_cycle' => $feeStructure->billing_cycle,
                    'due_date' => $installment->due_date,
                    'remarks' => $installment->remarks,
                ]);
            }

            return [[
                'key' => $this->academicChargeKey($feeStructure->id, null),
                'fee_structure_id' => $feeStructure->id,
                'fee_category_id' => $feeStructure->fee_category_id,
                'description' => $feeStructure->feeCategory?->name,
                'installment_no' => null,
                'unit_price' => (float) $feeStructure->amount,
                'amount' => (float) $feeStructure->amount,
                'billing_cycle' => $feeStructure->billing_cycle,
                'due_date' => null,
                'remarks' => $feeStructure->remarks,
            ]];
        })->values();
    }

    protected function academicChargeKey(int $feeStructureId, ?int $installmentNo): string
    {
        return 'fee-structure:' . $feeStructureId . ':installment:' . ($installmentNo ?? 'base');
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

    protected function studentOption(Student $student): array
    {
        $activeEnrollment = $student->enrollments->firstWhere('status', Enrollment::STATUS_ACTIVE);

        return [
            'id' => (string) $student->id,
            'label' => trim(
                $this->invoiceStudentName($student)
                . ' - ' . $student->admission_no
                . ($activeEnrollment?->academicYear ? ' - ' . $activeEnrollment->academicYear->name : '')
                . ($activeEnrollment?->grade ? ' - ' . $activeEnrollment->grade->name : '')
                . ($activeEnrollment?->feePlan ? ' - ' . $activeEnrollment->feePlan->name : '')
            ),
        ];
    }

    protected function invoiceStudentName(?Student $student): string
    {
        if (! $student) {
            return '—';
        }

        return trim(collect([
            trim((string) $student->preferred_name),
            trim((string) ($student->name_en ?: $student->full_name)),
            trim((string) $student->name_mm),
        ])->filter()->unique()->implode(' / ')) ?: '—';
    }

    protected function buildEnrollmentSummary(Student $student, int $academicYearId): ?array
    {
        $enrollment = Enrollment::query()
            ->with(['academicYear', 'grade', 'section', 'feePlan.feeStructures.feeCategory', 'feePlan.feeStructures.installments'])
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->first();

        $extraRegistrations = StudentExtraClassRegistration::query()
            ->with(['offering.catalog', 'offering.academicYear', 'offering.feeCategory'])
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->where('status', StudentExtraClassRegistration::STATUS_ACTIVE)
            ->orderByDesc('registration_date')
            ->orderByDesc('id')
            ->get();

        if (! $enrollment && $extraRegistrations->isEmpty()) {
            return null;
        }

        $feePlanItems = $enrollment
            ? $this->feePlanPreviewItems($this->applicableFeeStructuresForEnrollment($enrollment))
            : collect();
        $academicYearName = $enrollment?->academicYear?->name
            ?: $extraRegistrations->first()?->offering?->academicYear?->name;
        $billedPeriodsByRegistration = $this->billedPeriodsByRegistration($extraRegistrations->pluck('id')->all());

        return [
            'student_label' => $this->invoiceStudentName($student) . ' (' . $student->admission_no . ')',
            'student_type_label' => $enrollment
                ? 'Academic Student'
                : 'Extra Class Only Student',
            'academic_year' => $academicYearName,
            'grade' => $enrollment?->grade?->name,
            'grade_label' => $enrollment?->grade?->name
                ?: 'Extra Class Only Student',
            'section' => $enrollment?->section?->name,
            'section_label' => $enrollment?->section?->name
                ?: 'Not assigned for extra class only',
            'fee_plan' => $enrollment?->feePlan?->name,
            'fee_plan_label' => $enrollment?->feePlan?->name
                ?: ($enrollment ? 'No fee plan' : 'Not required for extra class only'),
            'fee_structures_count' => $enrollment?->feePlan
                ? $enrollment->feePlan->feeStructures->where('status', 'active')->count()
                : 0,
            'fee_plan_items_count' => $feePlanItems->count(),
            'fee_plan_total' => (float) $feePlanItems->sum('amount'),
            'fee_plan_items' => $feePlanItems->values()->all(),
            'has_enrollment' => (bool) $enrollment,
            'has_fee_plan' => (bool) $enrollment?->feePlan,
            'extra_registration_count' => $extraRegistrations->count(),
            'extra_registration_total_count' => $extraRegistrations->count(),
            'extra_registration_billed_count' => 0,
            'extra_registrations' => $extraRegistrations->map(function (StudentExtraClassRegistration $registration) use ($billedPeriodsByRegistration) {
                return [
                    'id' => $registration->id,
                    'label' => $registration->offering?->display_name,
                    'fee_amount' => (float) ($registration->offering?->fee_amount ?? 0),
                    'fee_category' => $registration->offering?->feeCategory?->name,
                    'registered_on' => optional($registration->registration_date)->format('Y-m-d'),
                    'billed_periods' => $billedPeriodsByRegistration[$registration->id] ?? [],
                ];
            })->values()->all(),
        ];
    }

    protected function billedPeriodsByRegistration(array $registrationIds): array
    {
        if ($registrationIds === []) {
            return [];
        }

        return StudentInvoiceItem::query()
            ->select(['student_extra_class_registration_id', 'student_invoice_id'])
            ->with('invoice:id,invoice_no,billing_period_type,billing_month,billing_quarter,billing_year_label,status')
            ->whereIn('student_extra_class_registration_id', $registrationIds)
            ->whereHas('invoice', fn ($query) => $query->whereNotIn('status', [StudentInvoice::STATUS_CANCELLED, StudentInvoice::STATUS_VOID]))
            ->get()
            ->groupBy('student_extra_class_registration_id')
            ->map(fn ($items) => $items->map(fn (StudentInvoiceItem $item) => [
                'billing_period_type' => $item->invoice?->billing_period_type,
                'billing_month' => $item->invoice?->billing_month,
                'billing_quarter' => $item->invoice?->billing_quarter,
                'billing_year_label' => $item->invoice?->billing_year_label,
                'invoice_no' => $item->invoice?->invoice_no,
            ])->values()->all())
            ->all();
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
