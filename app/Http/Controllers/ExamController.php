<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamRequest;
use App\Http\Requests\UpdateExamRequest;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('exams.index', [
            'exams' => Exam::query()
                ->with('academicYear')
                ->orderByDesc('start_date')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('exams.create', [
            'exam' => new Exam(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExamRequest $request): RedirectResponse
    {
        $exam = Exam::create($request->validated());
        app(AuditLogService::class)->log('academic', 'exams', 'created', $exam, [], app(AuditLogService::class)->modelState($exam), 'Created exam ' . $exam->name . '.');

        return redirect()
            ->route('exams.index')
            ->with('status', 'Exam created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Exam $exam): View
    {
        return view('exams.edit', [
            'exam' => $exam,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExamRequest $request, Exam $exam): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($exam);
        $exam->update($request->validated());
        $auditLogService->log('academic', 'exams', 'updated', $exam->fresh(), $beforeState, $auditLogService->modelState($exam->fresh()), 'Updated exam ' . $exam->name . '.');

        return redirect()
            ->route('exams.index')
            ->with('status', 'Exam updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Exam $exam): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($exam);
        $wasAlreadyClosed = $exam->status === 'closed';

        if (! $wasAlreadyClosed) {
            $exam->forceFill([
                'status' => 'closed',
            ])->save();

            $auditLogService->log('academic', 'exams', 'closed', $exam->fresh(), $beforeState, $auditLogService->modelState($exam->fresh()), 'Closed exam ' . $exam->name . '.');
        }

        return redirect()
            ->route('exams.index')
            ->with('status', $wasAlreadyClosed ? 'Exam already closed.' : 'Exam closed successfully.');
    }
}
