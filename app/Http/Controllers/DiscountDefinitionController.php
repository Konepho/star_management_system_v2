<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiscountDefinitionRequest;
use App\Http\Requests\UpdateDiscountDefinitionRequest;
use App\Models\DiscountDefinition;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DiscountDefinitionController extends Controller
{
    public function index(): View
    {
        return view('discount-definitions.index', [
            'discountDefinitions' => DiscountDefinition::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('discount-definitions.create', [
            'discountDefinition' => new DiscountDefinition(),
        ]);
    }

    public function store(StoreDiscountDefinitionRequest $request): RedirectResponse
    {
        $discountDefinition = DiscountDefinition::create($request->validated());
        app(AuditLogService::class)->log('finance', 'discount_definitions', 'created', $discountDefinition, [], app(AuditLogService::class)->modelState($discountDefinition), 'Created discount definition ' . $discountDefinition->name . '.');

        return redirect()
            ->route('discount-definitions.index')
            ->with('status', 'Discount definition created successfully.');
    }

    public function edit(DiscountDefinition $discountDefinition): View
    {
        return view('discount-definitions.edit', [
            'discountDefinition' => $discountDefinition,
        ]);
    }

    public function update(UpdateDiscountDefinitionRequest $request, DiscountDefinition $discountDefinition): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($discountDefinition);
        $discountDefinition->update($request->validated());
        $auditLogService->log('finance', 'discount_definitions', 'updated', $discountDefinition->fresh(), $beforeState, $auditLogService->modelState($discountDefinition->fresh()), 'Updated discount definition ' . $discountDefinition->name . '.');

        return redirect()
            ->route('discount-definitions.index')
            ->with('status', 'Discount definition updated successfully.');
    }

    public function destroy(DiscountDefinition $discountDefinition): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($discountDefinition);
        $discountDefinition->update([
            'status' => 'inactive',
        ]);
        $auditLogService->log('finance', 'discount_definitions', 'deactivated', $discountDefinition->fresh(), $beforeState, $auditLogService->modelState($discountDefinition->fresh()), 'Deactivated discount definition ' . $discountDefinition->name . '.');

        return redirect()
            ->route('discount-definitions.index')
            ->with('status', 'Discount definition deactivated successfully.');
    }
}
