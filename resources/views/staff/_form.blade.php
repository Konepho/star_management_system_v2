@props([
    'staff',
    'roles',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Staff',
])

@php
    $linkedUser = $staff->user;
    $createLoginAccount = old('create_login_account', $linkedUser ? '1' : '0');
    $selectedRoleId = old('role_id', $linkedUser?->roles->first()?->id);
    $assignmentAcademicYearId = old('assignment_academic_year_id', $staff->latestAssignedAcademicYearId() ?: $academicYears->firstWhere('is_current', true)?->id ?: $academicYears->first()?->id);
    $assignedSectionIds = collect(old('assigned_section_ids', $assignmentAcademicYearId ? $staff->assignedSectionIds((int) $assignmentAcademicYearId)->all() : []))
        ->map(fn ($sectionId) => (string) $sectionId)
        ->all();
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-8">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="staff_no" :value="__('Staff Number')" />
            <x-text-input id="staff_no" name="staff_no" type="text" class="mt-1 block w-full" :value="old('staff_no', $staff->staff_no)" required placeholder="STF-0001" />
            <x-input-error class="mt-2" :messages="$errors->get('staff_no')" />
        </div>

        <div>
            <x-input-label for="join_date" :value="__('Join Date')" />
            <x-text-input id="join_date" name="join_date" type="date" class="mt-1 block w-full" :value="old('join_date', optional($staff->join_date)->format('Y-m-d'))" />
            <x-input-error class="mt-2" :messages="$errors->get('join_date')" />
        </div>

        <div>
            <x-input-label for="first_name" :value="__('First Name')" />
            <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $staff->first_name)" required />
            <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
        </div>

        <div>
            <x-input-label for="last_name" :value="__('Last Name')" />
            <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $staff->last_name)" required />
            <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
        </div>

        <div>
            <x-input-label for="gender" :value="__('Gender')" />
            <select id="gender" name="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @php($selectedGender = old('gender', $staff->gender))
                <option value="">Select Gender</option>
                <option value="male" @selected($selectedGender === 'male')>Male</option>
                <option value="female" @selected($selectedGender === 'female')>Female</option>
                <option value="other" @selected($selectedGender === 'other')>Other</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('gender')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Employment Status')" />
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @php($selectedStatus = old('status', $staff->status ?: 'active'))
                <option value="active" @selected($selectedStatus === 'active')>Active</option>
                <option value="inactive" @selected($selectedStatus === 'inactive')>Inactive</option>
                <option value="on-leave" @selected($selectedStatus === 'on-leave')>On Leave</option>
                <option value="resigned" @selected($selectedStatus === 'resigned')>Resigned</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div>
            <x-input-label for="photo" :value="__('Staff Photo')" />
            <input id="photo" name="photo" type="file" accept="image/*" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500" />
            @if ($staff->photo_path)
                <p class="mt-1 text-xs text-slate-500">Current photo saved for ID cards.</p>
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('photo')" />
        </div>

        <div>
            <x-input-label for="department" :value="__('Department')" />
            <x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="old('department', $staff->department)" placeholder="Academics" />
            <x-input-error class="mt-2" :messages="$errors->get('department')" />
        </div>

        <div>
            <x-input-label for="designation" :value="__('Designation')" />
            <x-text-input id="designation" name="designation" type="text" class="mt-1 block w-full" :value="old('designation', $staff->designation)" placeholder="Teacher" />
            <x-input-error class="mt-2" :messages="$errors->get('designation')" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone')" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $staff->phone)" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Staff Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $staff->email)" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="address" :value="__('Address')" />
            <textarea id="address" name="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('address', $staff->address) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
        <div class="mb-6">
            <div class="text-sm font-semibold text-slate-900">Section Assignments</div>
            <p class="mt-1 text-sm text-slate-600">Assign classes for the selected academic year. Teacher and section-head scope will use these assignments.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="assignment_academic_year_id" :value="__('Assignment Academic Year')" />
                <select id="assignment_academic_year_id" name="assignment_academic_year_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">No class assignment</option>
                    @foreach ($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected((string) $assignmentAcademicYearId === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('assignment_academic_year_id')" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="assigned_section_ids" :value="__('Assigned Sections')" />
                <select id="assigned_section_ids" name="assigned_section_ids[]" multiple size="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" @selected(in_array((string) $section->id, $assignedSectionIds, true))>
                            {{ $section->grade?->name ? $section->grade->name . ' / ' : '' }}{{ $section->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Hold Ctrl or Command to select multiple sections.</p>
                <x-input-error class="mt-2" :messages="$errors->get('assigned_section_ids')" />
                <x-input-error class="mt-2" :messages="$errors->get('assigned_section_ids.*')" />
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
        <div class="flex items-start gap-3">
            <input id="create_login_account" name="create_login_account" type="checkbox" value="1" @checked($createLoginAccount === '1') class="mt-1 rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500">
            <div>
                <label for="create_login_account" class="text-sm font-semibold text-slate-900">Create or manage login account</label>
                <p class="mt-1 text-sm text-slate-600">Enable this if the staff member should sign in and use a role like Teacher or HR Manager.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="account_name" :value="__('Account Name')" />
                <x-text-input id="account_name" name="account_name" type="text" class="mt-1 block w-full" :value="old('account_name', $linkedUser?->name)" />
                <x-input-error class="mt-2" :messages="$errors->get('account_name')" />
            </div>

            <div>
                <x-input-label for="username" :value="__('Username')" />
                <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $linkedUser?->username)" placeholder="staff.username" />
                <x-input-error class="mt-2" :messages="$errors->get('username')" />
            </div>

            <div>
                <x-input-label for="user_email" :value="__('Login Email')" />
                <x-text-input id="user_email" name="user_email" type="email" class="mt-1 block w-full" :value="old('user_email', $linkedUser?->email)" />
                <x-input-error class="mt-2" :messages="$errors->get('user_email')" />
            </div>

            <div>
                <x-input-label for="user_phone" :value="__('Login Phone')" />
                <x-text-input id="user_phone" name="user_phone" type="text" class="mt-1 block w-full" :value="old('user_phone', $linkedUser?->phone)" />
                <x-input-error class="mt-2" :messages="$errors->get('user_phone')" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                <p class="mt-1 text-xs text-slate-500">{{ $linkedUser ? 'Leave blank to keep the current password.' : 'Use at least 8 characters.' }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('password')" />
            </div>

            <div>
                <x-input-label for="role_id" :value="__('System Role')" />
                <select id="role_id" name="role_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select Role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) $selectedRoleId === (string) $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('role_id')" />
            </div>

            <div class="md:col-span-2">
                <label for="is_active" class="inline-flex items-center gap-3">
                    <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $linkedUser?->is_active ?? true)) class="rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500">
                    <span class="text-sm font-medium text-slate-700">User account is active</span>
                </label>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('staff.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
