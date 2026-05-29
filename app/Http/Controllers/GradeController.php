<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGradeRequest;
use App\Http\Requests\UpdateGradeRequest;
use App\Models\Grade;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GradeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('grades.index', [
            'grades' => Grade::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('grades.create', [
            'grade' => new Grade(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGradeRequest $request): RedirectResponse
    {
        $grade = Grade::create($request->validated());
        app(AuditLogService::class)->log('settings', 'grades', 'created', $grade, [], app(AuditLogService::class)->modelState($grade), 'Created grade ' . $grade->name . '.');

        return redirect()
            ->route('grades.index')
            ->with('status', 'Grade created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Grade $grade): View
    {
        return view('grades.edit', compact('grade'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGradeRequest $request, Grade $grade): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($grade);
        $grade->update($request->validated());
        $auditLogService->log('settings', 'grades', 'updated', $grade->fresh(), $beforeState, $auditLogService->modelState($grade->fresh()), 'Updated grade ' . $grade->name . '.');

        return redirect()
            ->route('grades.index')
            ->with('status', 'Grade updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Grade $grade): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($grade);

        $hasHistory = $grade->sections()->exists()
            || $grade->enrollments()->exists()
            || $grade->feeStructures()->exists();

        if ($hasHistory) {
            return redirect()
                ->route('grades.index')
                ->with('status', 'Grade is already in use and cannot be deleted. Keep it for historical records.');
        }

        $grade->delete();
        $auditLogService->log('settings', 'grades', 'deleted', $grade, $beforeState, [], 'Deleted unused grade ' . $grade->name . '.');

        return redirect()
            ->route('grades.index')
            ->with('status', 'Grade deleted successfully.');
    }
}
