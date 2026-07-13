<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentExtraClassRegistrationRequest;
use App\Http\Requests\UpdateStudentExtraClassRegistrationRequest;
use App\Models\AcademicYear;
use App\Models\ExtraClassOffering;
use App\Models\Student;
use App\Models\StudentExtraClassRegistration;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentExtraClassRegistrationController extends Controller
{
    public function index(): View
    {
        $search = trim((string) request('search', ''));

        $registrations = StudentExtraClassRegistration::query()
            ->with(['student', 'academicYear', 'offering.catalog'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery
                        ->where('admission_no', 'like', '%' . $search . '%')
                        ->orWhere('name_en', 'like', '%' . $search . '%')
                        ->orWhere('name_mm', 'like', '%' . $search . '%')
                        ->orWhere('preferred_name', 'like', '%' . $search . '%')
                        ->orWhere('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%');
                })->orWhereHas('offering.catalog', function ($catalogQuery) use ($search) {
                    $catalogQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('registration_date')
            ->orderByDesc('id')
            ->get();

        return view('student-extra-class-registrations.index', [
            'registrations' => $registrations,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('student-extra-class-registrations.create', [
            'registration' => new StudentExtraClassRegistration([
                'status' => StudentExtraClassRegistration::STATUS_ACTIVE,
                'registration_date' => now()->toDateString(),
            ]),
            'students' => Student::query()->where('status', Student::STATUS_ACTIVE)->orderBy('name_en')->orderBy('first_name')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'offerings' => ExtraClassOffering::query()
                ->with(['catalog', 'academicYear'])
                ->where('status', 'active')
                ->orderByDesc('academic_year_id')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreStudentExtraClassRegistrationRequest $request): RedirectResponse
    {
        $registration = StudentExtraClassRegistration::query()->create($request->validated());

        app(AuditLogService::class)->log(
            'academic',
            'student_extra_class_registrations',
            'created',
            $registration->fresh(),
            [],
            app(AuditLogService::class)->modelState($registration->fresh()),
            'Created extra class registration for student #' . $registration->student_id . '.',
        );

        return redirect()->route('student-extra-class-registrations.index')->with('status', 'Student extra class registration created successfully.');
    }

    public function edit(StudentExtraClassRegistration $studentExtraClassRegistration): View
    {
        return view('student-extra-class-registrations.edit', [
            'registration' => $studentExtraClassRegistration,
            'students' => Student::query()->where('status', Student::STATUS_ACTIVE)->orderBy('name_en')->orderBy('first_name')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'offerings' => ExtraClassOffering::query()
                ->with(['catalog', 'academicYear'])
                ->orderByDesc('academic_year_id')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateStudentExtraClassRegistrationRequest $request, StudentExtraClassRegistration $studentExtraClassRegistration): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($studentExtraClassRegistration);

        $studentExtraClassRegistration->update($request->validated());

        $auditLogService->log(
            'academic',
            'student_extra_class_registrations',
            'updated',
            $studentExtraClassRegistration->fresh(),
            $beforeState,
            $auditLogService->modelState($studentExtraClassRegistration->fresh()),
            'Updated extra class registration #' . $studentExtraClassRegistration->id . '.',
        );

        return redirect()->route('student-extra-class-registrations.index')->with('status', 'Student extra class registration updated successfully.');
    }

    public function destroy(StudentExtraClassRegistration $studentExtraClassRegistration): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($studentExtraClassRegistration);
        $wasAlreadyCancelled = $studentExtraClassRegistration->status === StudentExtraClassRegistration::STATUS_CANCELLED;

        if (! $wasAlreadyCancelled) {
            $studentExtraClassRegistration->forceFill([
                'status' => StudentExtraClassRegistration::STATUS_CANCELLED,
            ])->save();

            $auditLogService->log(
                'academic',
                'student_extra_class_registrations',
                'cancelled',
                $studentExtraClassRegistration->fresh(),
                $beforeState,
                $auditLogService->modelState($studentExtraClassRegistration->fresh()),
                'Cancelled extra class registration #' . $studentExtraClassRegistration->id . '.',
            );
        }

        return redirect()->route('student-extra-class-registrations.index')->with('status', $wasAlreadyCancelled ? 'Registration already cancelled.' : 'Registration cancelled successfully.');
    }
}
