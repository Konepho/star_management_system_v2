<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExternalExamRegistrationRequest;
use App\Http\Requests\UpdateExternalExamRegistrationRequest;
use App\Models\ExternalExamRegistration;
use App\Models\ExternalExamSession;
use App\Models\Student;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExternalExamRegistrationController extends Controller
{
    public function index(): View
    {
        return view('external-exam-registrations.index', [
            'registrations' => ExternalExamRegistration::query()
                ->with(['student.activeEnrollments.grade', 'session.academicYear', 'payments'])
                ->orderByDesc('registration_date')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('external-exam-registrations.create', [
            'registration' => new ExternalExamRegistration([
                'registration_date' => now()->toDateString(),
                'status' => ExternalExamRegistration::STATUS_REGISTERED,
                'result_status' => ExternalExamRegistration::RESULT_PENDING,
            ]),
            'students' => $this->students(),
            'sessions' => $this->sessions(),
        ]);
    }

    public function store(StoreExternalExamRegistrationRequest $request): RedirectResponse
    {
        $registration = ExternalExamRegistration::query()->create($this->registrationAttributes($request->validated()));
        app(AuditLogService::class)->log(
            'academic',
            'external_exam_registrations',
            'created',
            $registration,
            [],
            app(AuditLogService::class)->modelState($registration),
            'Created external exam registration #' . $registration->id . '.',
        );

        return redirect()
            ->route('external-exam-registrations.show', $registration)
            ->with('status', 'External exam registration created successfully.');
    }

    public function show(ExternalExamRegistration $externalExamRegistration): View
    {
        return view('external-exam-registrations.show', [
            'registration' => $externalExamRegistration->load(['student.activeEnrollments.grade', 'student.activeEnrollments.section', 'session.academicYear', 'payments']),
        ]);
    }

    public function edit(ExternalExamRegistration $externalExamRegistration): View
    {
        return view('external-exam-registrations.edit', [
            'registration' => $externalExamRegistration,
            'students' => $this->students(),
            'sessions' => $this->sessions(),
        ]);
    }

    public function update(UpdateExternalExamRegistrationRequest $request, ExternalExamRegistration $externalExamRegistration): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($externalExamRegistration);
        $externalExamRegistration->update($this->registrationAttributes($request->validated()));

        $auditLogService->log(
            'academic',
            'external_exam_registrations',
            'updated',
            $externalExamRegistration->fresh(),
            $beforeState,
            $auditLogService->modelState($externalExamRegistration->fresh()),
            'Updated external exam registration #' . $externalExamRegistration->id . '.',
        );

        return redirect()
            ->route('external-exam-registrations.show', $externalExamRegistration)
            ->with('status', 'External exam registration updated successfully.');
    }

    public function destroy(ExternalExamRegistration $externalExamRegistration): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($externalExamRegistration);
        $wasAlreadyCancelled = $externalExamRegistration->status === ExternalExamRegistration::STATUS_CANCELLED;

        if (! $wasAlreadyCancelled) {
            $externalExamRegistration->forceFill([
                'status' => ExternalExamRegistration::STATUS_CANCELLED,
            ])->save();

            $auditLogService->log(
                'academic',
                'external_exam_registrations',
                'cancelled',
                $externalExamRegistration->fresh(),
                $beforeState,
                $auditLogService->modelState($externalExamRegistration->fresh()),
                'Cancelled external exam registration #' . $externalExamRegistration->id . '.',
            );
        }

        return redirect()
            ->route('external-exam-registrations.index')
            ->with('status', $wasAlreadyCancelled ? 'External exam registration already cancelled.' : 'External exam registration cancelled successfully.');
    }

    protected function students()
    {
        return Student::query()
            ->with(['activeEnrollments.academicYear', 'activeEnrollments.grade', 'activeEnrollments.feePlan'])
            ->orderBy('name_en')
            ->orderBy('first_name')
            ->get();
    }

    protected function sessions()
    {
        return ExternalExamSession::query()
            ->with('academicYear')
            ->orderByDesc('exam_date')
            ->orderBy('name')
            ->get();
    }

    protected function registrationAttributes(array $validated): array
    {
        $feeAmount = (float) ($validated['fee_amount'] ?? 0);
        $discountAmount = (float) ($validated['discount_amount'] ?? 0);

        return array_merge($validated, [
            'total_amount' => max(0, $feeAmount - $discountAmount),
        ]);
    }
}
