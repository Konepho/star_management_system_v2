<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeeCategoryRequest;
use App\Http\Requests\UpdateFeeCategoryRequest;
use App\Models\FeeCategory;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FeeCategoryController extends Controller
{
    public function index(): View
    {
        return view('fee-categories.index', [
            'feeCategories' => FeeCategory::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('fee-categories.create', [
            'feeCategory' => new FeeCategory(),
        ]);
    }

    public function store(StoreFeeCategoryRequest $request): RedirectResponse
    {
        $feeCategory = FeeCategory::create($request->validated());
        app(AuditLogService::class)->log('finance', 'fee_categories', 'created', $feeCategory, [], app(AuditLogService::class)->modelState($feeCategory), 'Created fee category ' . $feeCategory->name . '.');

        return redirect()
            ->route('fee-categories.index')
            ->with('status', 'Fee category created successfully.');
    }

    public function edit(FeeCategory $feeCategory): View
    {
        return view('fee-categories.edit', [
            'feeCategory' => $feeCategory,
        ]);
    }

    public function update(UpdateFeeCategoryRequest $request, FeeCategory $feeCategory): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($feeCategory);
        $feeCategory->update($request->validated());
        $auditLogService->log('finance', 'fee_categories', 'updated', $feeCategory->fresh(), $beforeState, $auditLogService->modelState($feeCategory->fresh()), 'Updated fee category ' . $feeCategory->name . '.');

        return redirect()
            ->route('fee-categories.index')
            ->with('status', 'Fee category updated successfully.');
    }

    public function destroy(FeeCategory $feeCategory): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($feeCategory);
        $wasAlreadyInactive = $feeCategory->status === 'inactive';

        if (! $wasAlreadyInactive) {
            $feeCategory->forceFill([
                'status' => 'inactive',
            ])->save();

            $auditLogService->log(
                'finance',
                'fee_categories',
                'deactivated',
                $feeCategory->fresh(),
                $beforeState,
                $auditLogService->modelState($feeCategory->fresh()),
                'Deactivated fee category ' . $feeCategory->name . '.',
            );
        }

        return redirect()
            ->route('fee-categories.index')
            ->with('status', $wasAlreadyInactive ? 'Fee category already inactive.' : 'Fee category deactivated successfully.');
    }
}
