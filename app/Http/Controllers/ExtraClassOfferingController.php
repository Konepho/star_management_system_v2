<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtraClassOfferingRequest;
use App\Http\Requests\UpdateExtraClassOfferingRequest;
use App\Models\AcademicYear;
use App\Models\ExtraClassCatalog;
use App\Models\ExtraClassOffering;
use App\Models\FeeCategory;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExtraClassOfferingController extends Controller
{
    public function index(): View
    {
        return view('extra-class-offerings.index', [
            'offerings' => ExtraClassOffering::query()
                ->with(['catalog', 'academicYear', 'feeCategory'])
                ->withCount('registrations')
                ->orderByDesc(
                    AcademicYear::query()
                        ->select('start_date')
                        ->whereColumn('academic_years.id', 'extra_class_offerings.academic_year_id')
                )
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('extra-class-offerings.create', [
            'offering' => new ExtraClassOffering(['status' => 'active']),
            'catalogs' => ExtraClassCatalog::query()->where('status', 'active')->orderBy('name')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'feeCategories' => FeeCategory::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreExtraClassOfferingRequest $request): RedirectResponse
    {
        $offering = ExtraClassOffering::query()->create($request->validated());

        app(AuditLogService::class)->log(
            'finance',
            'extra_class_offerings',
            'created',
            $offering->fresh(),
            [],
            app(AuditLogService::class)->modelState($offering->fresh()),
            'Created extra class offering ' . $offering->display_name . '.',
        );

        return redirect()->route('extra-class-offerings.index')->with('status', 'Extra class offering created successfully.');
    }

    public function edit(ExtraClassOffering $extraClassOffering): View
    {
        return view('extra-class-offerings.edit', [
            'offering' => $extraClassOffering,
            'catalogs' => ExtraClassCatalog::query()->orderBy('name')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'feeCategories' => FeeCategory::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateExtraClassOfferingRequest $request, ExtraClassOffering $extraClassOffering): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($extraClassOffering);

        $extraClassOffering->update($request->validated());

        $auditLogService->log(
            'finance',
            'extra_class_offerings',
            'updated',
            $extraClassOffering->fresh(),
            $beforeState,
            $auditLogService->modelState($extraClassOffering->fresh()),
            'Updated extra class offering ' . $extraClassOffering->display_name . '.',
        );

        return redirect()->route('extra-class-offerings.index')->with('status', 'Extra class offering updated successfully.');
    }

    public function destroy(ExtraClassOffering $extraClassOffering): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($extraClassOffering);
        $wasAlreadyInactive = $extraClassOffering->status === 'inactive';

        if (! $wasAlreadyInactive) {
            $extraClassOffering->forceFill(['status' => 'inactive'])->save();

            $auditLogService->log(
                'finance',
                'extra_class_offerings',
                'deactivated',
                $extraClassOffering->fresh(),
                $beforeState,
                $auditLogService->modelState($extraClassOffering->fresh()),
                'Deactivated extra class offering ' . $extraClassOffering->display_name . '.',
            );
        }

        return redirect()->route('extra-class-offerings.index')->with('status', $wasAlreadyInactive ? 'Extra class offering already inactive.' : 'Extra class offering deactivated successfully.');
    }
}
