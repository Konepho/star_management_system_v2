<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeePlanRequest;
use App\Http\Requests\UpdateFeePlanRequest;
use App\Models\AcademicYear;
use App\Models\FeePlan;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FeePlanController extends Controller
{
    public function index(): View
    {
        return view('fee-plans.index', [
            'feePlans' => FeePlan::query()
                ->with(['academicYear', 'feeStructures.feeCategory'])
                ->orderByDesc(
                    AcademicYear::query()
                        ->select('start_date')
                        ->whereColumn('academic_years.id', 'fee_plans.academic_year_id')
                )
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('fee-plans.create', [
            'feePlan' => new FeePlan(['status' => 'active']),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'feeStructures' => FeeStructure::query()
                ->with(['academicYear', 'feeCategory', 'grade'])
                ->orderByDesc('academic_year_id')
                ->orderBy('fee_category_id')
                ->get(),
        ]);
    }

    public function store(StoreFeePlanRequest $request): RedirectResponse
    {
        $feePlan = DB::transaction(function () use ($request): FeePlan {
            $feePlan = FeePlan::query()->create($request->safe()->except('fee_structure_ids'));
            $feePlan->feeStructures()->sync($request->validated('fee_structure_ids'));
            return $feePlan;
        });

        app(AuditLogService::class)->log(
            'finance',
            'fee_plans',
            'created',
            $feePlan->fresh(),
            [],
            app(AuditLogService::class)->modelState($feePlan->fresh()),
            'Created fee plan ' . $feePlan->name . '.',
            ['fee_structure_ids' => $feePlan->feeStructures()->pluck('fee_structures.id')->all()],
        );

        return redirect()
            ->route('fee-plans.index')
            ->with('status', 'Fee plan created successfully.');
    }

    public function edit(FeePlan $feePlan): View
    {
        return view('fee-plans.edit', [
            'feePlan' => $feePlan->load('feeStructures'),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'feeStructures' => FeeStructure::query()
                ->with(['academicYear', 'feeCategory', 'grade'])
                ->orderByDesc('academic_year_id')
                ->orderBy('fee_category_id')
                ->get(),
        ]);
    }

    public function update(UpdateFeePlanRequest $request, FeePlan $feePlan): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($feePlan);
        $beforeMeta = ['fee_structure_ids' => $feePlan->feeStructures()->pluck('fee_structures.id')->all()];

        DB::transaction(function () use ($request, $feePlan): void {
            $feePlan->update($request->safe()->except('fee_structure_ids'));
            $feePlan->feeStructures()->sync($request->validated('fee_structure_ids'));
        });

        $auditLogService->log(
            'finance',
            'fee_plans',
            'updated',
            $feePlan->fresh(),
            $beforeState,
            $auditLogService->modelState($feePlan->fresh()),
            'Updated fee plan ' . $feePlan->name . '.',
            [
                'before' => $beforeMeta,
                'after' => ['fee_structure_ids' => $feePlan->feeStructures()->pluck('fee_structures.id')->all()],
            ],
        );

        return redirect()
            ->route('fee-plans.index')
            ->with('status', 'Fee plan updated successfully.');
    }

    public function destroy(FeePlan $feePlan): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($feePlan);
        $wasAlreadyInactive = $feePlan->status === 'inactive';

        if (! $wasAlreadyInactive) {
            $feePlan->forceFill([
                'status' => 'inactive',
            ])->save();

            $auditLogService->log(
                'finance',
                'fee_plans',
                'deactivated',
                $feePlan->fresh(),
                $beforeState,
                $auditLogService->modelState($feePlan->fresh()),
                'Deactivated fee plan ' . $feePlan->name . '.',
            );
        }

        return redirect()
            ->route('fee-plans.index')
            ->with('status', $wasAlreadyInactive ? 'Fee plan already inactive.' : 'Fee plan deactivated successfully.');
    }
}
