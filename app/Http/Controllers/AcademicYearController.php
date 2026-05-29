<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcademicYearRequest;
use App\Http\Requests\UpdateAcademicYearRequest;
use App\Models\AcademicYear;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('academic-years.index', [
            'academicYears' => AcademicYear::query()
                ->orderByDesc('is_current')
                ->orderByDesc('start_date')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('academic-years.create', [
            'academicYear' => new AcademicYear(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAcademicYearRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($data['is_current']) {
            AcademicYear::query()->update(['is_current' => false]);
        }

        $academicYear = AcademicYear::create($data);
        app(AuditLogService::class)->log('settings', 'academic_years', 'created', $academicYear, [], app(AuditLogService::class)->modelState($academicYear), 'Created academic year ' . $academicYear->name . '.');

        return redirect()
            ->route('academic-years.index')
            ->with('status', 'Academic year created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AcademicYear $academicYear): View
    {
        return view('academic-years.edit', compact('academicYear'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($academicYear);
        $data = $request->validated();

        if ($data['is_current']) {
            AcademicYear::query()
                ->whereKeyNot($academicYear->id)
                ->update(['is_current' => false]);
        }

        $academicYear->update($data);
        $auditLogService->log('settings', 'academic_years', 'updated', $academicYear->fresh(), $beforeState, $auditLogService->modelState($academicYear->fresh()), 'Updated academic year ' . $academicYear->name . '.');

        return redirect()
            ->route('academic-years.index')
            ->with('status', 'Academic year updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($academicYear);
        $wasAlreadyClosed = $academicYear->status === 'closed';

        if (! $wasAlreadyClosed) {
            $academicYear->forceFill([
                'status' => 'closed',
                'is_current' => false,
            ])->save();

            $auditLogService->log('settings', 'academic_years', 'closed', $academicYear->fresh(), $beforeState, $auditLogService->modelState($academicYear->fresh()), 'Closed academic year ' . $academicYear->name . '.');
        }

        return redirect()
            ->route('academic-years.index')
            ->with('status', $wasAlreadyClosed ? 'Academic year already closed.' : 'Academic year closed successfully.');
    }
}
