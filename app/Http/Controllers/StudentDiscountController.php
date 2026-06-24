<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentDiscountRequest;
use App\Http\Requests\UpdateStudentDiscountRequest;
use App\Models\DiscountDefinition;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentDiscountController extends Controller
{
    public function index(): View
    {
        return view('student-discounts.index', [
            'studentDiscounts' => StudentDiscount::query()
                ->with(['student.activeEnrollments.grade', 'student.activeEnrollments.section', 'discountDefinition'])
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('student-discounts.create', [
            'studentDiscount' => new StudentDiscount([
                'start_date' => now()->toDateString(),
                'status' => 'active',
            ]),
            'students' => $this->students(),
            'discountDefinitions' => $this->discountDefinitions(),
        ]);
    }

    public function store(StoreStudentDiscountRequest $request): RedirectResponse
    {
        $studentDiscount = StudentDiscount::create($request->validated());
        app(AuditLogService::class)->log(
            'finance',
            'student_discounts',
            'created',
            $studentDiscount,
            [],
            app(AuditLogService::class)->modelState($studentDiscount),
            'Assigned student discount #' . $studentDiscount->id . '.',
        );

        return redirect()
            ->route('student-discounts.index')
            ->with('status', 'Student discount assigned successfully.');
    }

    public function edit(StudentDiscount $studentDiscount): View
    {
        return view('student-discounts.edit', [
            'studentDiscount' => $studentDiscount,
            'students' => $this->students(),
            'discountDefinitions' => $this->discountDefinitions(),
        ]);
    }

    public function update(UpdateStudentDiscountRequest $request, StudentDiscount $studentDiscount): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($studentDiscount);
        $studentDiscount->update($request->validated());

        $auditLogService->log(
            'finance',
            'student_discounts',
            'updated',
            $studentDiscount->fresh(),
            $beforeState,
            $auditLogService->modelState($studentDiscount->fresh()),
            'Updated student discount #' . $studentDiscount->id . '.',
        );

        return redirect()
            ->route('student-discounts.index')
            ->with('status', 'Student discount updated successfully.');
    }

    public function destroy(StudentDiscount $studentDiscount): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($studentDiscount);
        $wasAlreadyInactive = $studentDiscount->status === 'inactive';

        if (! $wasAlreadyInactive) {
            $studentDiscount->forceFill([
                'status' => 'inactive',
                'end_date' => $studentDiscount->end_date ?? now()->toDateString(),
            ])->save();

            $auditLogService->log(
                'finance',
                'student_discounts',
                'deactivated',
                $studentDiscount->fresh(),
                $beforeState,
                $auditLogService->modelState($studentDiscount->fresh()),
                'Deactivated student discount #' . $studentDiscount->id . '.',
            );
        }

        return redirect()
            ->route('student-discounts.index')
            ->with('status', $wasAlreadyInactive ? 'Student discount already inactive.' : 'Student discount deactivated successfully.');
    }

    protected function students()
    {
        return Student::query()
            ->with(['activeEnrollments.academicYear', 'activeEnrollments.grade', 'activeEnrollments.section'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    protected function discountDefinitions()
    {
        return DiscountDefinition::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }
}
