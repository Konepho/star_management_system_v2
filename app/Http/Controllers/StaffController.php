<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        return view('staff.index', [
            'staffMembers' => Staff::query()
                ->with(['user.roles'])
                ->orderByDesc('join_date')
                ->orderBy('staff_no')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('staff.create', [
            'staff' => new Staff(),
            'roles' => Role::query()->official()->orderBy('name')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'sections' => Section::query()->with('grade')->orderBy('grade_id')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $staff = DB::transaction(function () use ($request): Staff {
            $validated = $request->validated();
            $staffData = $request->staffData($validated);
            $staffData['photo_path'] = $this->storePhoto($request);

            $user = $this->persistUserAccount($request, $validated);

            if ($user) {
                $staffData['user_id'] = $user->id;
            }

            $staff = Staff::create($staffData);
            $this->syncSectionAssignments($staff, $validated);
            return $staff;
        });

        $staff->load(['user.roles', 'sectionAssignments']);
        app(AuditLogService::class)->log(
            'academic',
            'staff',
            'created',
            $staff,
            [],
            app(AuditLogService::class)->modelState($staff),
            'Created staff member ' . $staff->display_name . '.',
            [
                'role_ids' => $staff->user?->roles?->pluck('id')->all() ?? [],
                'section_assignments' => $staff->sectionAssignments->map->only(['academic_year_id', 'section_id'])->all(),
            ],
        );

        return redirect()
            ->route('staff.index')
            ->with('status', 'Staff member created successfully.');
    }

    public function edit(Staff $staff): View
    {
        $staff->load(['user.roles']);

        return view('staff.edit', [
            'staff' => $staff,
            'roles' => Role::query()->official()->orderBy('name')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'sections' => Section::query()->with('grade')->orderBy('grade_id')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateStaffRequest $request, Staff $staff): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $staff->load(['user.roles', 'sectionAssignments']);
        $beforeState = $auditLogService->modelState($staff);
        $beforeMeta = [
            'role_ids' => $staff->user?->roles?->pluck('id')->all() ?? [],
            'section_assignments' => $staff->sectionAssignments->map->only(['academic_year_id', 'section_id'])->all(),
        ];

        DB::transaction(function () use ($request, $staff): void {
            $validated = $request->validated();
            $staffData = $request->staffData($validated);

            if ($request->hasFile('photo')) {
                $staffData['photo_path'] = $this->storePhoto($request, $staff->photo_path);
            }

            $user = $this->persistUserAccount($request, $validated, $staff->user);

            if ($user) {
                $staffData['user_id'] = $user->id;
            }

            $staff->update($staffData);
            $this->syncSectionAssignments($staff, $validated);
        });

        $staff->refresh()->load(['user.roles', 'sectionAssignments']);
        $auditLogService->log(
            'academic',
            'staff',
            'updated',
            $staff,
            $beforeState,
            $auditLogService->modelState($staff),
            'Updated staff member ' . $staff->display_name . '.',
            [
                'before' => $beforeMeta,
                'after' => [
                    'role_ids' => $staff->user?->roles?->pluck('id')->all() ?? [],
                    'section_assignments' => $staff->sectionAssignments->map->only(['academic_year_id', 'section_id'])->all(),
                ],
            ],
        );

        return redirect()
            ->route('staff.index')
            ->with('status', 'Staff member updated successfully.');
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($staff);
        $staff->status = 'inactive';
        $staff->save();

        if ($staff->user) {
            $staff->user->update([
                'is_active' => false,
            ]);
        }

        $auditLogService->log(
            'academic',
            'staff',
            'deactivated',
            $staff->fresh('user'),
            $beforeState,
            $auditLogService->modelState($staff->fresh()),
            'Deactivated staff member ' . $staff->display_name . '.',
        );

        return redirect()
            ->route('staff.index')
            ->with('status', 'Staff member deactivated successfully.');
    }

    private function persistUserAccount(StoreStaffRequest|UpdateStaffRequest $request, array $validated, ?User $user = null): ?User
    {
        if (! $request->shouldCreateUserAccount($validated)) {
            return $user;
        }

        $accountData = $request->userData($validated);

        if (! $user) {
            $user = User::create($accountData);
        } else {
            if (blank($accountData['password'] ?? null)) {
                unset($accountData['password']);
            }

            $user->update($accountData);
        }

        if (isset($validated['role_id']) && $validated['role_id']) {
            $user->roles()->sync([$validated['role_id']]);
        }

        return $user;
    }

    private function storePhoto(StoreStaffRequest|UpdateStaffRequest $request, ?string $existingPath = null): ?string
    {
        if (! $request->hasFile('photo')) {
            return $existingPath;
        }

        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        return $request->file('photo')->store('staff-photos', 'public');
    }

    private function syncSectionAssignments(Staff $staff, array $validated): void
    {
        $academicYearId = isset($validated['assignment_academic_year_id']) ? (int) $validated['assignment_academic_year_id'] : null;

        if (! $academicYearId) {
            return;
        }

        $sectionIds = collect($validated['assigned_section_ids'] ?? [])
            ->map(fn (mixed $sectionId) => (int) $sectionId)
            ->filter()
            ->unique()
            ->values();

        $staff->sectionAssignments()
            ->where('academic_year_id', $academicYearId)
            ->delete();

        if ($sectionIds->isEmpty()) {
            return;
        }

        $staff->sectionAssignments()->createMany(
            $sectionIds->map(fn (int $sectionId) => [
                'academic_year_id' => $academicYearId,
                'section_id' => $sectionId,
            ])->all()
        );
    }
}
