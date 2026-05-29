<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentPaymentRequest;
use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use App\Services\AuditLogService;
use App\Services\DocumentNumberService;
use App\Services\InvoicePaymentTimingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentPaymentController extends Controller
{
    public function __construct(
        protected InvoicePaymentTimingService $invoicePaymentTimingService,
        protected DocumentNumberService $documentNumberService,
    ) {
    }

    public function index(): View
    {
        return view('student-payments.index', [
            'payments' => StudentPayment::query()
                ->with(['invoice.student', 'invoice.grade'])
                ->orderByDesc('payment_date')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function store(StoreStudentPaymentRequest $request): RedirectResponse
    {
        /** @var StudentInvoice $invoice */
        $invoice = StudentInvoice::query()->with(['payments', 'items.feeCategory', 'discounts', 'student.discounts.discountDefinition'])->findOrFail($request->validated('student_invoice_id'));

        $payment = DB::transaction(function () use ($request, $invoice) {
            $this->invoicePaymentTimingService->resolveForPayment($invoice, $request->validated('payment_date'));
            $invoice->refresh()->load('payments');

            if ((float) $request->validated('amount') > $invoice->balance_due) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount cannot be greater than the remaining invoice balance after discount and late-fee adjustments.',
                ]);
            }

            $payment = StudentPayment::create([
                'receipt_no' => $this->documentNumberService->nextReceiptNumber($invoice->academicYear),
                'student_invoice_id' => $invoice->id,
                'student_id' => $invoice->student_id,
                'payment_date' => $request->validated('payment_date'),
                'amount' => $request->validated('amount'),
                'payment_method' => $request->validated('payment_method'),
                'reference_no' => $request->validated('reference_no'),
                'notes' => $request->validated('notes'),
            ]);

            $invoice->load('payments');
            $invoice->refreshPaymentStatus();

            return $payment;
        });

        app(AuditLogService::class)->log(
            'finance',
            'student_payments',
            'collected',
            $payment,
            [],
            app(AuditLogService::class)->modelState($payment),
            'Collected student payment ' . $payment->receipt_no . '.',
            [
                'invoice_no' => $invoice->invoice_no,
                'student_id' => $invoice->student_id,
                'amount' => (float) $payment->amount,
            ],
        );

        return redirect()
            ->route('student-payments.show', $payment)
            ->with('status', 'Payment collected successfully.');
    }

    public function show(StudentPayment $studentPayment): View
    {
        return view('student-payments.show', [
            'payment' => $studentPayment->load(['invoice.student', 'invoice.academicYear', 'invoice.grade', 'invoice.section', 'invoice.items', 'invoice.discounts.discountDefinition', 'invoice.payments']),
        ]);
    }

    public function destroy(StudentPayment $studentPayment): RedirectResponse
    {
        $invoice = $studentPayment->invoice()->first();
        $wasAlreadyReversed = $studentPayment->isReversed();
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($studentPayment);

        DB::transaction(function () use ($studentPayment, $invoice): void {
            if ($studentPayment->isReversed()) {
                return;
            }

            $studentPayment->forceFill([
                'reversed_at' => now(),
                'reversal_reason' => 'Reversed from payment management.',
            ])->save();

            if ($invoice) {
                $invoice->load(['payments', 'items.feeCategory', 'discounts', 'student.discounts.discountDefinition']);
                $this->invoicePaymentTimingService->resetIfNoPaymentsRemain($invoice);
                $invoice->load('payments');
                $invoice->refreshPaymentStatus();
            }
        });

        if (! $wasAlreadyReversed) {
            $auditLogService->log(
                'finance',
                'student_payments',
                'reversed',
                $studentPayment->fresh(),
                $beforeState,
                $auditLogService->modelState($studentPayment->fresh()),
                'Reversed student payment ' . $studentPayment->receipt_no . '.',
                [
                    'invoice_no' => $invoice?->invoice_no,
                    'student_id' => $studentPayment->student_id,
                ],
            );
        }

        return redirect()
            ->route('student-payments.index')
            ->with('status', $wasAlreadyReversed ? 'Payment already reversed.' : 'Payment reversed successfully.');
    }
}
