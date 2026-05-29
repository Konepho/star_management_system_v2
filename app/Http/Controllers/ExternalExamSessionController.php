<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExternalExamSessionRequest;
use App\Http\Requests\UpdateExternalExamSessionRequest;
use App\Models\AcademicYear;
use App\Models\ExternalExamSession;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExternalExamSessionController extends Controller
{
    public function index(): View
    {
        return view('external-exam-sessions.index', [
            'sessions' => ExternalExamSession::query()
                ->with('academicYear')
                ->orderByDesc('exam_date')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('external-exam-sessions.create', [
            'session' => new ExternalExamSession([
                'status' => ExternalExamSession::STATUS_OPEN,
            ]),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
        ]);
    }

    public function store(StoreExternalExamSessionRequest $request): RedirectResponse
    {
        $session = ExternalExamSession::query()->create($request->validated());
        app(AuditLogService::class)->log('academic', 'external_exam_sessions', 'created', $session, [], app(AuditLogService::class)->modelState($session), 'Created external exam session ' . $session->name . '.');

        return redirect()
            ->route('external-exam-sessions.index')
            ->with('status', 'External exam session created successfully.');
    }

    public function edit(ExternalExamSession $externalExamSession): View
    {
        return view('external-exam-sessions.edit', [
            'session' => $externalExamSession,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
        ]);
    }

    public function update(UpdateExternalExamSessionRequest $request, ExternalExamSession $externalExamSession): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($externalExamSession);
        $externalExamSession->update($request->validated());
        $auditLogService->log('academic', 'external_exam_sessions', 'updated', $externalExamSession->fresh(), $beforeState, $auditLogService->modelState($externalExamSession->fresh()), 'Updated external exam session ' . $externalExamSession->name . '.');

        return redirect()
            ->route('external-exam-sessions.index')
            ->with('status', 'External exam session updated successfully.');
    }

    public function destroy(ExternalExamSession $externalExamSession): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($externalExamSession);
        $externalExamSession->update([
            'status' => ExternalExamSession::STATUS_CANCELLED,
        ]);
        $auditLogService->log('academic', 'external_exam_sessions', 'cancelled', $externalExamSession->fresh(), $beforeState, $auditLogService->modelState($externalExamSession->fresh()), 'Cancelled external exam session ' . $externalExamSession->name . '.');

        return redirect()
            ->route('external-exam-sessions.index')
            ->with('status', 'External exam session cancelled successfully.');
    }
}
