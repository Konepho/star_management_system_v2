<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\AuditLogService;
use App\Support\SectionScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $students = Student::query()
            ->with(['activeEnrollments.academicYear', 'activeEnrollments.grade', 'activeEnrollments.section', 'fatherGuardian', 'motherGuardian'])
            ->orderByDesc('admission_date')
            ->orderBy('admission_no');

        SectionScope::restrictStudentEnrollmentScope($students, request()->user(), 'activeEnrollments');

        return view('students.index', [
            'students' => $students->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('students.create', [
            'student' => new Student(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $student = DB::transaction(function () use ($request): Student {
            $validated = $request->validated();
            $studentAttributes = $this->studentAttributes($validated);
            $studentAttributes['photo_path'] = $this->storePhoto($request);

            $student = Student::create($studentAttributes);

            $this->syncGuardians($student, $validated);
            $this->syncHealthProfile($student, $validated);
            return $student;
        });

        $student->load(['guardians', 'healthProfile']);
        app(AuditLogService::class)->log(
            'academic',
            'students',
            'created',
            $student,
            [],
            app(AuditLogService::class)->modelState($student),
            'Created student ' . $student->full_name . '.',
            [
                'guardians' => $student->guardians->map->only(['relation', 'name', 'phone', 'email'])->all(),
                'health_profile' => $student->healthProfile?->only([
                    'blood_type',
                    'allergies',
                    'medical_conditions',
                    'medications',
                    'doctor_name',
                    'doctor_phone',
                    'emergency_medical_note',
                    'health_remark',
                ]),
            ],
        );

        return redirect()
            ->route('students.index')
            ->with('status', 'Student created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student): View
    {
        abort_unless(SectionScope::canAccessStudent(request()->user(), $student, 'activeEnrollments'), 403);

        $student->load([
            'fatherGuardian',
            'motherGuardian',
            'healthProfile',
            'enrollments' => fn ($query) => $query
                ->with(['academicYear', 'grade', 'section', 'feePlan'])
                ->where('status', \App\Models\Enrollment::STATUS_ACTIVE)
                ->orderByDesc('enrollment_date')
                ->orderByDesc('id'),
        ]);

        return view('students.edit', [
            'student' => $student,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        abort_unless(SectionScope::canAccessStudent($request->user(), $student, 'activeEnrollments'), 403);
        $auditLogService = app(AuditLogService::class);
        $student->load(['guardians', 'healthProfile']);
        $beforeState = $auditLogService->modelState($student);
        $beforeMeta = [
            'guardians' => $student->guardians->map->only(['relation', 'name', 'phone', 'email'])->all(),
            'health_profile' => $student->healthProfile?->only([
                'blood_type',
                'allergies',
                'medical_conditions',
                'medications',
                'doctor_name',
                'doctor_phone',
                'emergency_medical_note',
                'health_remark',
            ]),
        ];

        DB::transaction(function () use ($request, $student): void {
            $validated = $request->validated();
            $studentAttributes = $this->studentAttributes($validated, $student);

            if ($request->hasFile('photo')) {
                $studentAttributes['photo_path'] = $this->storePhoto($request, $student->photo_path);
            }

            $student->update($studentAttributes);
            $this->syncGuardians($student, $validated);
            $this->syncHealthProfile($student, $validated);
        });

        $student->refresh()->load(['guardians', 'healthProfile']);
        $auditLogService->log(
            'academic',
            'students',
            'updated',
            $student,
            $beforeState,
            $auditLogService->modelState($student),
            'Updated student ' . $student->full_name . '.',
            [
                'before' => $beforeMeta,
                'after' => [
                    'guardians' => $student->guardians->map->only(['relation', 'name', 'phone', 'email'])->all(),
                    'health_profile' => $student->healthProfile?->only([
                        'blood_type',
                        'allergies',
                        'medical_conditions',
                        'medications',
                        'doctor_name',
                        'doctor_phone',
                        'emergency_medical_note',
                        'health_remark',
                    ]),
                ],
            ],
        );

        return redirect()
            ->route('students.index')
            ->with('status', 'Student updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student): RedirectResponse
    {
        abort_unless(SectionScope::canAccessStudent(request()->user(), $student, 'activeEnrollments'), 403);
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($student);

        if ($student->status !== Student::STATUS_ARCHIVED) {
            $student->forceFill([
                'status' => Student::STATUS_ARCHIVED,
                'archived_at' => now(),
            ])->save();

            $auditLogService->log(
                'academic',
                'students',
                'archived',
                $student->fresh(),
                $beforeState,
                $auditLogService->modelState($student->fresh()),
                'Archived student ' . $student->full_name . '.',
            );
        }

        return redirect()
            ->route('students.index')
            ->with('status', 'Student archived successfully.');
    }

    private function studentAttributes(array $validated, ?Student $student = null): array
    {
        $attributes = Arr::only($validated, [
            'admission_no',
            'name_mm',
            'name_en',
            'preferred_name',
            'gender',
            'student_type',
            'previous_school_name',
            'date_of_birth',
            'admission_date',
            'email',
            'contact_number',
            'emergency_contact_number',
            'address',
            'card_color',
            'status',
        ]);

        $attributes['first_name'] = $validated['name_en'];
        $attributes['last_name'] = '';
        $attributes['phone'] = $validated['contact_number'] ?? null;
        $attributes['archived_at'] = ($validated['status'] ?? null) === Student::STATUS_ARCHIVED
            ? ($student?->archived_at ?? now())
            : null;

        return $attributes;
    }

    private function storePhoto(StoreStudentRequest|UpdateStudentRequest $request, ?string $existingPath = null): ?string
    {
        if (! $request->hasFile('photo')) {
            return $existingPath;
        }

        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        return $request->file('photo')->store('student-photos', 'public');
    }

    private function syncGuardians(Student $student, array $validated): void
    {
        $this->syncGuardianByRelation($student, Guardian::RELATION_FATHER, [
            'name' => $validated['father_name'] ?? null,
            'occupation' => $validated['father_occupation'] ?? null,
            'phone' => $validated['father_phone'] ?? null,
            'email' => $validated['father_email'] ?? null,
            'is_primary_contact' => false,
            'is_emergency_contact' => false,
        ]);

        $this->syncGuardianByRelation($student, Guardian::RELATION_MOTHER, [
            'name' => $validated['mother_name'] ?? null,
            'occupation' => $validated['mother_occupation'] ?? null,
            'phone' => $validated['mother_phone'] ?? null,
            'email' => $validated['mother_email'] ?? null,
            'is_primary_contact' => false,
            'is_emergency_contact' => false,
        ]);
    }

    private function syncGuardianByRelation(Student $student, string $relation, array $attributes): void
    {
        $hasValue = collect($attributes)
            ->except(['is_primary_contact', 'is_emergency_contact'])
            ->filter(fn ($value) => filled($value))
            ->isNotEmpty();

        $guardian = $student->guardians()->firstOrNew([
            'relation' => $relation,
        ]);

        if (! $hasValue) {
            if ($guardian->exists) {
                $guardian->delete();
            }

            return;
        }

        $guardian->fill($attributes);
        $guardian->relation = $relation;
        $student->guardians()->save($guardian);
    }

    private function syncHealthProfile(Student $student, array $validated): void
    {
        $attributes = Arr::only($validated, [
            'blood_type',
            'allergies',
            'medical_conditions',
            'medications',
            'doctor_name',
            'doctor_phone',
            'emergency_medical_note',
            'health_remark',
        ]);

        $hasValue = collect($attributes)->filter(fn ($value) => filled($value))->isNotEmpty();
        $profile = $student->healthProfile()->firstOrNew();

        if (! $hasValue) {
            if ($profile->exists) {
                $profile->delete();
            }

            return;
        }

        $profile->fill($attributes);
        $student->healthProfile()->save($profile);
    }
}
