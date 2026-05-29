<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Models\Grade;
use App\Models\Room;
use App\Models\Section;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('sections.index', [
            'sections' => Section::query()
                ->with(['grade', 'room'])
                ->select('sections.*')
                ->join('grades', 'grades.id', '=', 'sections.grade_id')
                ->orderBy('grades.sort_order')
                ->orderBy('sections.name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('sections.create', [
            'section' => new Section(),
            'grades' => Grade::query()->orderBy('sort_order')->orderBy('name')->get(),
            'rooms' => Room::query()->where('status', Room::STATUS_ACTIVE)->orderBy('building')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSectionRequest $request): RedirectResponse
    {
        $section = Section::create($request->validated());
        app(AuditLogService::class)->log('settings', 'sections', 'created', $section, [], app(AuditLogService::class)->modelState($section), 'Created section ' . $section->name . '.');

        return redirect()
            ->route('sections.index')
            ->with('status', 'Section created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Section $section): View
    {
        return view('sections.edit', [
            'section' => $section,
            'grades' => Grade::query()->orderBy('sort_order')->orderBy('name')->get(),
            'rooms' => Room::query()->orderBy('building')->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSectionRequest $request, Section $section): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($section);
        $section->update($request->validated());
        $auditLogService->log('settings', 'sections', 'updated', $section->fresh(), $beforeState, $auditLogService->modelState($section->fresh()), 'Updated section ' . $section->name . '.');

        return redirect()
            ->route('sections.index')
            ->with('status', 'Section updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Section $section): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($section);
        $wasAlreadyClosed = $section->status === 'closed';

        if (! $wasAlreadyClosed) {
            $section->forceFill([
                'status' => 'closed',
            ])->save();

            $auditLogService->log('settings', 'sections', 'closed', $section->fresh(), $beforeState, $auditLogService->modelState($section->fresh()), 'Closed section ' . $section->name . '.');
        }

        return redirect()
            ->route('sections.index')
            ->with('status', $wasAlreadyClosed ? 'Section already closed.' : 'Section closed successfully.');
    }
}
