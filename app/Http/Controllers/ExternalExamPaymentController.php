<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExternalExamPaymentRequest;
use App\Models\ExternalExamPayment;
use App\Services\AuditLogService;
use App\Services\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExternalExamPaymentController extends Controller
{
    public function __construct(protected DocumentNumberService $documentNumberService)
    {
    }

    public function index(): View
    {
        $search = trim((string) request('search', ''));

        $paymentsQuery = ExternalExamPayment::query()
            ->with(['registration.student', 'registration.session.academicYear']);

        if ($search !== '') {
            $paymentsQuery->where(function ($query) use ($search) {
                $query->where('receipt_no', 'like', '%' . $search . '%')
                    ->orWhere('reference_no', 'like', '%' . $search . '%')
                    ->orWhereHas('registration.student', function ($studentQuery) use ($search) {
                        $studentQuery
                            ->where('admission_no', 'like', '%' . $search . '%')
                            ->orWhere('name_en', 'like', '%' . $search . '%')
                            ->orWhere('name_mm', 'like', '%' . $search . '%')
                            ->orWhere('preferred_name', 'like', '%' . $search . '%')
                            ->orWhere('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('registration.session', function ($sessionQuery) use ($search) {
                        $sessionQuery
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('exam_body', 'like', '%' . $search . '%')
                            ->orWhere('level', 'like', '%' . $search . '%');
                    });
            });
        }

        return view('external-exam-payments.index', [
            'payments' => $paymentsQuery
                ->orderByDesc('payment_date')
                ->orderByDesc('id')
                ->get(),
            'search' => $search,
        ]);
    }

    public function store(StoreExternalExamPaymentRequest $request): RedirectResponse
    {
        $registration = $request->registration();
        $request->ensureAmountWithinBalance($registration);

        $payment = DB::transaction(function () use ($request, $registration) {
            return ExternalExamPayment::query()->create([
                'receipt_no' => $this->documentNumberService->nextExternalExamReceiptNumber($registration->session?->academicYear),
                'external_exam_registration_id' => $registration->id,
                'payment_date' => $request->validated('payment_date'),
                'amount' => $request->validated('amount'),
                'payment_method' => $request->validated('payment_method'),
                'reference_no' => $request->validated('reference_no'),
                'notes' => $request->validated('notes'),
            ]);
        });

        app(AuditLogService::class)->log(
            'finance',
            'external_exam_payments',
            'collected',
            $payment,
            [],
            app(AuditLogService::class)->modelState($payment),
            'Collected external exam payment ' . $payment->receipt_no . '.',
            [
                'registration_id' => $registration->id,
                'student_id' => $registration->student_id,
                'session_id' => $registration->external_exam_session_id,
                'amount' => (float) $payment->amount,
            ],
        );

        return redirect()
            ->route('external-exam-registrations.show', $registration)
            ->with('status', 'External exam payment collected successfully. Receipt: ' . $payment->receipt_no);
    }

    public function show(ExternalExamPayment $externalExamPayment): View
    {
        return view('external-exam-payments.show', [
            'payment' => $externalExamPayment->load(['registration.student', 'registration.session.academicYear', 'registration.session']),
        ]);
    }

    public function destroy(ExternalExamPayment $externalExamPayment): RedirectResponse
    {
        $registration = $externalExamPayment->registration()->first();
        $wasAlreadyReversed = $externalExamPayment->isReversed();
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($externalExamPayment);

        if (! $wasAlreadyReversed) {
            $externalExamPayment->forceFill([
                'reversed_at' => now(),
                'reversal_reason' => 'Reversed from external exam payment management.',
            ])->save();

            $auditLogService->log(
                'finance',
                'external_exam_payments',
                'reversed',
                $externalExamPayment->fresh(),
                $beforeState,
                $auditLogService->modelState($externalExamPayment->fresh()),
                'Reversed external exam payment ' . $externalExamPayment->receipt_no . '.',
                [
                    'registration_id' => $registration?->id,
                    'student_id' => $registration?->student_id,
                ],
            );
        }

        return redirect()
            ->route('external-exam-registrations.show', $registration)
            ->with('status', $wasAlreadyReversed ? 'External exam payment already reversed.' : 'External exam payment reversed successfully.');
    }
}
