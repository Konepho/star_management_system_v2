<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Models\Subject;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('subjects.index', [
            'subjects' => Subject::query()
                ->orderByDesc('is_core')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('subjects.create', [
            'subject' => new Subject(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $subject = Subject::create($request->validated());
        app(AuditLogService::class)->log('settings', 'subjects', 'created', $subject, [], app(AuditLogService::class)->modelState($subject), 'Created subject ' . $subject->name . '.');

        return redirect()
            ->route('subjects.index')
            ->with('status', 'Subject created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subject $subject): View
    {
        return view('subjects.edit', compact('subject'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($subject);
        $subject->update($request->validated());
        $auditLogService->log('settings', 'subjects', 'updated', $subject->fresh(), $beforeState, $auditLogService->modelState($subject->fresh()), 'Updated subject ' . $subject->name . '.');

        return redirect()
            ->route('subjects.index')
            ->with('status', 'Subject updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subject $subject): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($subject);
        $wasAlreadyInactive = $subject->status === 'inactive';

        if (! $wasAlreadyInactive) {
            $subject->forceFill([
                'status' => 'inactive',
            ])->save();

            $auditLogService->log('settings', 'subjects', 'deactivated', $subject->fresh(), $beforeState, $auditLogService->modelState($subject->fresh()), 'Deactivated subject ' . $subject->name . '.');
        }

        return redirect()
            ->route('subjects.index')
            ->with('status', $wasAlreadyInactive ? 'Subject already inactive.' : 'Subject deactivated successfully.');
    }
}
