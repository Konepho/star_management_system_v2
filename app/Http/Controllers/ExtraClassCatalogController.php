<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtraClassCatalogRequest;
use App\Http\Requests\UpdateExtraClassCatalogRequest;
use App\Models\ExtraClassCatalog;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExtraClassCatalogController extends Controller
{
    public function index(): View
    {
        return view('extra-class-catalogs.index', [
            'catalogs' => ExtraClassCatalog::query()
                ->withCount('offerings')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('extra-class-catalogs.create', [
            'catalog' => new ExtraClassCatalog(['status' => 'active']),
        ]);
    }

    public function store(StoreExtraClassCatalogRequest $request): RedirectResponse
    {
        $catalog = ExtraClassCatalog::query()->create($request->validated());

        app(AuditLogService::class)->log(
            'finance',
            'extra_class_catalogs',
            'created',
            $catalog->fresh(),
            [],
            app(AuditLogService::class)->modelState($catalog->fresh()),
            'Created extra class catalog ' . $catalog->name . '.',
        );

        return redirect()->route('extra-class-catalogs.index')->with('status', 'Extra class catalog created successfully.');
    }

    public function edit(ExtraClassCatalog $extraClassCatalog): View
    {
        return view('extra-class-catalogs.edit', [
            'catalog' => $extraClassCatalog,
        ]);
    }

    public function update(UpdateExtraClassCatalogRequest $request, ExtraClassCatalog $extraClassCatalog): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($extraClassCatalog);

        $extraClassCatalog->update($request->validated());

        $auditLogService->log(
            'finance',
            'extra_class_catalogs',
            'updated',
            $extraClassCatalog->fresh(),
            $beforeState,
            $auditLogService->modelState($extraClassCatalog->fresh()),
            'Updated extra class catalog ' . $extraClassCatalog->name . '.',
        );

        return redirect()->route('extra-class-catalogs.index')->with('status', 'Extra class catalog updated successfully.');
    }

    public function destroy(ExtraClassCatalog $extraClassCatalog): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($extraClassCatalog);
        $wasAlreadyInactive = $extraClassCatalog->status === 'inactive';

        if (! $wasAlreadyInactive) {
            $extraClassCatalog->forceFill(['status' => 'inactive'])->save();

            $auditLogService->log(
                'finance',
                'extra_class_catalogs',
                'deactivated',
                $extraClassCatalog->fresh(),
                $beforeState,
                $auditLogService->modelState($extraClassCatalog->fresh()),
                'Deactivated extra class catalog ' . $extraClassCatalog->name . '.',
            );
        }

        return redirect()->route('extra-class-catalogs.index')->with('status', $wasAlreadyInactive ? 'Extra class catalog already inactive.' : 'Extra class catalog deactivated successfully.');
    }
}
