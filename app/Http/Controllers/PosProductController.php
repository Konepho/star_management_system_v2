<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePosProductRequest;
use App\Http\Requests\UpdatePosProductRequest;
use App\Models\PosProduct;
use App\Models\PosProductCategory;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PosProductController extends Controller
{
    public function index(): View
    {
        return view('pos-products.index', [
            'products' => PosProduct::query()->with('category')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('pos-products.create', [
            'product' => new PosProduct(['status' => 'active']),
            'categories' => PosProductCategory::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(StorePosProductRequest $request): RedirectResponse
    {
        $product = PosProduct::query()->create($request->validated());
        app(AuditLogService::class)->log('finance', 'pos_products', 'created', $product, [], app(AuditLogService::class)->modelState($product), 'Created POS product ' . $product->name . '.');

        return redirect()
            ->route('pos-products.index')
            ->with('status', 'POS product created successfully.');
    }

    public function edit(PosProduct $posProduct): View
    {
        return view('pos-products.edit', [
            'product' => $posProduct,
            'categories' => PosProductCategory::query()
                ->where(function ($query) use ($posProduct) {
                    $query->where('status', 'active');

                    if ($posProduct->pos_product_category_id) {
                        $query->orWhere('id', $posProduct->pos_product_category_id);
                    }
                })
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdatePosProductRequest $request, PosProduct $posProduct): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($posProduct);
        $posProduct->update($request->validated());
        $auditLogService->log('finance', 'pos_products', 'updated', $posProduct->fresh(), $beforeState, $auditLogService->modelState($posProduct->fresh()), 'Updated POS product ' . $posProduct->name . '.');

        return redirect()
            ->route('pos-products.index')
            ->with('status', 'POS product updated successfully.');
    }

    public function destroy(PosProduct $posProduct): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($posProduct);
        $wasAlreadyInactive = $posProduct->status === 'inactive';

        if (! $wasAlreadyInactive) {
            $posProduct->forceFill([
                'status' => 'inactive',
            ])->save();

            $auditLogService->log('finance', 'pos_products', 'deactivated', $posProduct->fresh(), $beforeState, $auditLogService->modelState($posProduct->fresh()), 'Deactivated POS product ' . $posProduct->name . '.');
        }

        return redirect()
            ->route('pos-products.index')
            ->with('status', $wasAlreadyInactive ? 'POS product already inactive.' : 'POS product deactivated successfully.');
    }
}
