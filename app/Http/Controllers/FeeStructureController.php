<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeeStructureRequest;
use App\Http\Requests\UpdateFeeStructureRequest;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FeeStructureController extends Controller
{
    public function index(): View
    {
        return view('fee-structures.index', [
            'feeStructures' => FeeStructure::query()
                ->with(['academicYear', 'grade', 'feeCategory', 'installments'])
                ->orderByDesc(
                    AcademicYear::query()
                        ->select('start_date')
                        ->whereColumn('academic_years.id', 'fee_structures.academic_year_id')
                )
                ->orderBy(
                    Grade::query()
                        ->select('sort_order')
                        ->whereColumn('grades.id', 'fee_structures.grade_id')
                )
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('fee-structures.create', [
            'feeStructure' => new FeeStructure(['billing_cycle' => 'monthly', 'status' => 'active']),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'grades' => Grade::query()->orderBy('sort_order')->orderBy('name')->get(),
            'feeCategories' => FeeCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreFeeStructureRequest $request): RedirectResponse
    {
        $feeStructure = DB::transaction(function () use ($request): FeeStructure {
            $feeStructure = FeeStructure::create($request->safe()->except(['fee_scope', 'installments']));

            $this->syncInstallments($feeStructure, data_get($request->validated(), 'installments', []));
            return $feeStructure;
        });

        $feeStructure->load('installments');
        app(AuditLogService::class)->log(
            'finance',
            'fee_structures',
            'created',
            $feeStructure,
            [],
            app(AuditLogService::class)->modelState($feeStructure),
            'Created fee structure #' . $feeStructure->id . '.',
            ['installments' => $feeStructure->installments->map->only(['installment_no', 'amount', 'due_date'])->all()],
        );

        return redirect()
            ->route('fee-structures.index')
            ->with('status', 'Fee structure created successfully.');
    }

    public function edit(FeeStructure $feeStructure): View
    {
        return view('fee-structures.edit', [
            'feeStructure' => $feeStructure->load('installments'),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'grades' => Grade::query()->orderBy('sort_order')->orderBy('name')->get(),
            'feeCategories' => FeeCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateFeeStructureRequest $request, FeeStructure $feeStructure): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $feeStructure->load('installments');
        $beforeState = $auditLogService->modelState($feeStructure);
        $beforeMeta = ['installments' => $feeStructure->installments->map->only(['installment_no', 'amount', 'due_date'])->all()];

        DB::transaction(function () use ($request, $feeStructure): void {
            $feeStructure->update($request->safe()->except(['fee_scope', 'installments']));

            $this->syncInstallments($feeStructure, data_get($request->validated(), 'installments', []));
        });

        $feeStructure->refresh()->load('installments');
        $auditLogService->log(
            'finance',
            'fee_structures',
            'updated',
            $feeStructure,
            $beforeState,
            $auditLogService->modelState($feeStructure),
            'Updated fee structure #' . $feeStructure->id . '.',
            [
                'before' => $beforeMeta,
                'after' => ['installments' => $feeStructure->installments->map->only(['installment_no', 'amount', 'due_date'])->all()],
            ],
        );

        return redirect()
            ->route('fee-structures.index')
            ->with('status', 'Fee structure updated successfully.');
    }

    public function destroy(FeeStructure $feeStructure): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($feeStructure);
        $wasAlreadyInactive = $feeStructure->status === 'inactive';

        if (! $wasAlreadyInactive) {
            $feeStructure->forceFill([
                'status' => 'inactive',
            ])->save();

            $auditLogService->log(
                'finance',
                'fee_structures',
                'deactivated',
                $feeStructure->fresh(),
                $beforeState,
                $auditLogService->modelState($feeStructure->fresh()),
                'Deactivated fee structure #' . $feeStructure->id . '.',
            );
        }

        return redirect()
            ->route('fee-structures.index')
            ->with('status', $wasAlreadyInactive ? 'Fee structure already inactive.' : 'Fee structure deactivated successfully.');
    }

    protected function syncInstallments(FeeStructure $feeStructure, array $installments): void
    {
        if ($feeStructure->billing_cycle !== 'installment') {
            $feeStructure->installments()->delete();

            return;
        }

        $feeStructure->installments()->delete();

        $payload = collect($installments)
            ->values()
            ->map(function (array $installment, int $index): array {
                return [
                    'installment_no' => $index + 1,
                    'amount' => $installment['amount'],
                    'due_date' => $installment['due_date'] ?: null,
                    'remarks' => $installment['remarks'] ?: null,
                ];
            })
            ->all();

        if ($payload !== []) {
            $feeStructure->installments()->createMany($payload);
        }
    }
}
