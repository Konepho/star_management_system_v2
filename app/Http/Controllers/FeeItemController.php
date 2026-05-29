<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeeItemRequest;
use App\Http\Requests\UpdateFeeItemRequest;
use App\Models\FeeCategory;
use App\Models\FeeItem;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FeeItemController extends Controller
{
    public function index(): View
    {
        return view('fee-items.index', [
            'feeItems' => FeeItem::query()
                ->with('feeCategory')
                ->orderBy('name')
                ->orderBy('variant')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('fee-items.create', [
            'feeItem' => new FeeItem(['status' => 'active']),
            'feeCategories' => FeeCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreFeeItemRequest $request): RedirectResponse
    {
        $feeItem = FeeItem::create($request->validated());
        app(AuditLogService::class)->log('finance', 'fee_items', 'created', $feeItem, [], app(AuditLogService::class)->modelState($feeItem), 'Created fee item ' . $feeItem->name . '.');

        return redirect()
            ->route('fee-items.index')
            ->with('status', 'Fee item created successfully.');
    }

    public function edit(FeeItem $feeItem): View
    {
        return view('fee-items.edit', [
            'feeItem' => $feeItem,
            'feeCategories' => FeeCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateFeeItemRequest $request, FeeItem $feeItem): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($feeItem);
        $feeItem->update($request->validated());
        $auditLogService->log('finance', 'fee_items', 'updated', $feeItem->fresh(), $beforeState, $auditLogService->modelState($feeItem->fresh()), 'Updated fee item ' . $feeItem->name . '.');

        return redirect()
            ->route('fee-items.index')
            ->with('status', 'Fee item updated successfully.');
    }

    public function destroy(FeeItem $feeItem): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($feeItem);
        $wasAlreadyInactive = $feeItem->status === 'inactive';

        if (! $wasAlreadyInactive) {
            $feeItem->forceFill([
                'status' => 'inactive',
            ])->save();

            $auditLogService->log(
                'finance',
                'fee_items',
                'deactivated',
                $feeItem->fresh(),
                $beforeState,
                $auditLogService->modelState($feeItem->fresh()),
                'Deactivated fee item ' . $feeItem->name . '.',
            );
        }

        return redirect()
            ->route('fee-items.index')
            ->with('status', $wasAlreadyInactive ? 'Fee item already inactive.' : 'Fee item deactivated successfully.');
    }
}
