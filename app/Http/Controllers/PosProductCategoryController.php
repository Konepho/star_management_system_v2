<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePosProductCategoryRequest;
use App\Http\Requests\UpdatePosProductCategoryRequest;
use App\Models\PosProductCategory;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PosProductCategoryController extends Controller
{
    public function index(): View
    {
        return view('pos-product-categories.index', [
            'categories' => PosProductCategory::query()
                ->withCount('products')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('pos-product-categories.create', [
            'category' => new PosProductCategory(['status' => 'active']),
        ]);
    }

    public function store(StorePosProductCategoryRequest $request): RedirectResponse
    {
        $category = PosProductCategory::query()->create($request->validated());
        app(AuditLogService::class)->log('finance', 'pos_product_categories', 'created', $category, [], app(AuditLogService::class)->modelState($category), 'Created POS product category ' . $category->name . '.');

        return redirect()
            ->route('pos-product-categories.index')
            ->with('status', 'POS product category created successfully.');
    }

    public function edit(PosProductCategory $posProductCategory): View
    {
        return view('pos-product-categories.edit', [
            'category' => $posProductCategory,
        ]);
    }

    public function update(UpdatePosProductCategoryRequest $request, PosProductCategory $posProductCategory): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($posProductCategory);
        $posProductCategory->update($request->validated());
        $auditLogService->log('finance', 'pos_product_categories', 'updated', $posProductCategory->fresh(), $beforeState, $auditLogService->modelState($posProductCategory->fresh()), 'Updated POS product category ' . $posProductCategory->name . '.');

        return redirect()
            ->route('pos-product-categories.index')
            ->with('status', 'POS product category updated successfully.');
    }

    public function destroy(PosProductCategory $posProductCategory): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($posProductCategory);
        $wasAlreadyInactive = $posProductCategory->status === 'inactive';

        if (! $wasAlreadyInactive) {
            $posProductCategory->forceFill([
                'status' => 'inactive',
            ])->save();

            $auditLogService->log('finance', 'pos_product_categories', 'deactivated', $posProductCategory->fresh(), $beforeState, $auditLogService->modelState($posProductCategory->fresh()), 'Deactivated POS product category ' . $posProductCategory->name . '.');
        }

        return redirect()
            ->route('pos-product-categories.index')
            ->with('status', $wasAlreadyInactive ? 'POS product category already inactive.' : 'POS product category deactivated successfully.');
    }
}
