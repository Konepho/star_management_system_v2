@props([
    'student',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Student',
])

@php
    $currentEnrollment = $student->relationLoaded('enrollments')
        ? $student->enrollments->first()
        : null;
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-8">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
        This form creates the student profile only. After saving, use <a href="{{ route('enrollments.index') }}" class="font-semibold underline">Enrollments</a> to assign academic year, grade, section, and fee plan placement.
    </div>

    @if ($student->exists)
        <section class="space-y-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Current Enrollment Summary</h3>
                        <p class="mt-1 text-sm text-slate-500">Class placement and fee plan are managed from Enrollments, not from the student profile form.</p>
                    </div>
                    <a href="{{ route('enrollments.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Open Enrollments
                    </a>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Academic Year</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $currentEnrollment?->academicYear?->name ?? 'Not enrolled' }}</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Grade</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $currentEnrollment?->grade?->name ?? '—' }}</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Section</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $currentEnrollment?->section?->name ?? '—' }}</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Fee Plan</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $currentEnrollment?->feePlan?->name ?? 'Not assigned' }}</div>
                    </div>
                </div>
            </div>
        </section>
    @endif


    <section class="space-y-4">
        <div>
            <h3 class="text-base font-semibold text-slate-900">Student Profile</h3>
            <p class="text-sm text-slate-500">Store the student's main identity and contact information here.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="admission_no" :value="__('Admission Number')" />
                <x-text-input id="admission_no" name="admission_no" type="text" class="mt-1 block w-full" :value="old('admission_no', $student->admission_no)" required placeholder="STU-0001" />
                <x-input-error class="mt-2" :messages="$errors->get('admission_no')" />
            </div>

            <div>
                <x-input-label for="student_type" :value="__('Student Type')" />
                @php($selectedStudentType = old('student_type', $student->student_type))
                <select id="student_type" name="student_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select Student Type</option>
                    <option value="new" @selected($selectedStudentType === 'new')>New Student</option>
                    <option value="old" @selected($selectedStudentType === 'old')>Old Student</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('student_type')" />
            </div>

            <div>
                <x-input-label for="name_mm" :value="__('Student Name (Burmese)')" />
                <x-text-input id="name_mm" name="name_mm" type="text" class="mt-1 block w-full" :value="old('name_mm', $student->name_mm)" required />
                <x-input-error class="mt-2" :messages="$errors->get('name_mm')" />
            </div>

            <div>
                <x-input-label for="name_en" :value="__('Student Name (English)')" />
                <x-text-input id="name_en" name="name_en" type="text" class="mt-1 block w-full" :value="old('name_en', $student->name_en ?: $student->full_name)" required />
                <x-input-error class="mt-2" :messages="$errors->get('name_en')" />
            </div>

            <div>
                <x-input-label for="preferred_name" :value="__('Preferred / English Name')" />
                <x-text-input id="preferred_name" name="preferred_name" type="text" class="mt-1 block w-full" :value="old('preferred_name', $student->preferred_name)" placeholder="Optional nickname or preferred English name" />
                <x-input-error class="mt-2" :messages="$errors->get('preferred_name')" />
            </div>

            <div>
                <x-input-label for="previous_school_name" :value="__('Previous School Name')" />
                <x-text-input id="previous_school_name" name="previous_school_name" type="text" class="mt-1 block w-full" :value="old('previous_school_name', $student->previous_school_name)" />
                <x-input-error class="mt-2" :messages="$errors->get('previous_school_name')" />
            </div>

            <div>
                <x-input-label for="photo" :value="__('Student Photo')" />
                <input id="photo" name="photo" type="file" accept="image/*" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500" />
                @if ($student->photo_path)
                    <p class="mt-1 text-xs text-slate-500">Current photo saved for ID cards.</p>
                @endif
                <x-input-error class="mt-2" :messages="$errors->get('photo')" />
            </div>

            <div>
                <x-input-label for="card_color" :value="__('Student Card Color')" />
                @php($selectedCardColor = old('card_color', $student->card_color ?: 'yellow'))
                <select id="card_color" name="card_color" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    <option value="yellow" @selected($selectedCardColor === 'yellow')>Yellow</option>
                    <option value="red" @selected($selectedCardColor === 'red')>Red</option>
                    <option value="blue" @selected($selectedCardColor === 'blue')>Blue</option>
                    <option value="green" @selected($selectedCardColor === 'green')>Green</option>
                    <option value="orange" @selected($selectedCardColor === 'orange')>Orange</option>
                </select>
                <p class="mt-1 text-xs text-slate-500">This controls the printed student ID card color.</p>
                <x-input-error class="mt-2" :messages="$errors->get('card_color')" />
            </div>

            <div>
                <x-input-label for="gender" :value="__('Gender')" />
                <select id="gender" name="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @php($selectedGender = old('gender', $student->gender))
                    <option value="">Select Gender</option>
                    <option value="male" @selected($selectedGender === 'male')>Male</option>
                    <option value="female" @selected($selectedGender === 'female')>Female</option>
                    <option value="other" @selected($selectedGender === 'other')>Other</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('gender')" />
            </div>

            <div>
                <x-input-label for="date_of_birth" :value="__('Date of Birth')" />
                <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth', optional($student->date_of_birth)->format('Y-m-d'))" />
                <x-input-error class="mt-2" :messages="$errors->get('date_of_birth')" />
            </div>

            <div>
                <x-input-label for="admission_date" :value="__('Admission Date')" />
                <x-text-input id="admission_date" name="admission_date" type="date" class="mt-1 block w-full" :value="old('admission_date', optional($student->admission_date)->format('Y-m-d'))" />
                <x-input-error class="mt-2" :messages="$errors->get('admission_date')" />
            </div>

            <div>
                <x-input-label for="status" :value="__('Status')" />
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    @php($selectedStatus = old('status', $student->status ?: 'active'))
                    <option value="active" @selected($selectedStatus === 'active')>Active</option>
                    <option value="inactive" @selected($selectedStatus === 'inactive')>Inactive</option>
                    <option value="graduated" @selected($selectedStatus === 'graduated')>Graduated</option>
                    <option value="transferred" @selected($selectedStatus === 'transferred')>Transferred</option>
                    <option value="archived" @selected($selectedStatus === 'archived')>Archived</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('status')" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Student Email')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $student->email)" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>

            <div>
                <x-input-label for="contact_number" :value="__('Contact Number')" />
                <x-text-input id="contact_number" name="contact_number" type="text" class="mt-1 block w-full" :value="old('contact_number', $student->contact_number ?: $student->phone)" />
                <x-input-error class="mt-2" :messages="$errors->get('contact_number')" />
            </div>

            <div>
                <x-input-label for="emergency_contact_number" :value="__('Emergency Contact Number')" />
                <x-text-input id="emergency_contact_number" name="emergency_contact_number" type="text" class="mt-1 block w-full" :value="old('emergency_contact_number', $student->emergency_contact_number)" />
                <x-input-error class="mt-2" :messages="$errors->get('emergency_contact_number')" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="address" :value="__('Address')" />
                <textarea id="address" name="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('address', $student->address) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('address')" />
            </div>
        </div>
    </section>

    <section class="space-y-4 border-t border-slate-200 pt-6">
        <div>
            <h3 class="text-base font-semibold text-slate-900">Guardian Information</h3>
            <p class="text-sm text-slate-500">Store father and mother information separately so guardian records can grow later.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-slate-200 p-5">
                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Father</h4>
                <div class="mt-4 grid gap-4">
                    <div>
                        <x-input-label for="father_name" :value="__('Father Name')" />
                        <x-text-input id="father_name" name="father_name" type="text" class="mt-1 block w-full" :value="old('father_name', optional($student->fatherGuardian)->name)" />
                        <x-input-error class="mt-2" :messages="$errors->get('father_name')" />
                    </div>
                    <div>
                        <x-input-label for="father_occupation" :value="__('Father Occupation')" />
                        <x-text-input id="father_occupation" name="father_occupation" type="text" class="mt-1 block w-full" :value="old('father_occupation', optional($student->fatherGuardian)->occupation)" />
                        <x-input-error class="mt-2" :messages="$errors->get('father_occupation')" />
                    </div>
                    <div>
                        <x-input-label for="father_phone" :value="__('Father Phone')" />
                        <x-text-input id="father_phone" name="father_phone" type="text" class="mt-1 block w-full" :value="old('father_phone', optional($student->fatherGuardian)->phone)" />
                        <x-input-error class="mt-2" :messages="$errors->get('father_phone')" />
                    </div>
                    <div>
                        <x-input-label for="father_email" :value="__('Father Email')" />
                        <x-text-input id="father_email" name="father_email" type="email" class="mt-1 block w-full" :value="old('father_email', optional($student->fatherGuardian)->email)" />
                        <x-input-error class="mt-2" :messages="$errors->get('father_email')" />
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 p-5">
                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Mother</h4>
                <div class="mt-4 grid gap-4">
                    <div>
                        <x-input-label for="mother_name" :value="__('Mother Name')" />
                        <x-text-input id="mother_name" name="mother_name" type="text" class="mt-1 block w-full" :value="old('mother_name', optional($student->motherGuardian)->name)" />
                        <x-input-error class="mt-2" :messages="$errors->get('mother_name')" />
                    </div>
                    <div>
                        <x-input-label for="mother_occupation" :value="__('Mother Occupation')" />
                        <x-text-input id="mother_occupation" name="mother_occupation" type="text" class="mt-1 block w-full" :value="old('mother_occupation', optional($student->motherGuardian)->occupation)" />
                        <x-input-error class="mt-2" :messages="$errors->get('mother_occupation')" />
                    </div>
                    <div>
                        <x-input-label for="mother_phone" :value="__('Mother Phone')" />
                        <x-text-input id="mother_phone" name="mother_phone" type="text" class="mt-1 block w-full" :value="old('mother_phone', optional($student->motherGuardian)->phone)" />
                        <x-input-error class="mt-2" :messages="$errors->get('mother_phone')" />
                    </div>
                    <div>
                        <x-input-label for="mother_email" :value="__('Mother Email')" />
                        <x-text-input id="mother_email" name="mother_email" type="email" class="mt-1 block w-full" :value="old('mother_email', optional($student->motherGuardian)->email)" />
                        <x-input-error class="mt-2" :messages="$errors->get('mother_email')" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="space-y-4 border-t border-slate-200 pt-6">
        <div>
            <h3 class="text-base font-semibold text-slate-900">Health Profile</h3>
            <p class="text-sm text-slate-500">Health information is stored separately so you can safely expand it later.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="blood_type" :value="__('Blood Type')" />
                <x-text-input id="blood_type" name="blood_type" type="text" class="mt-1 block w-full" :value="old('blood_type', optional($student->healthProfile)->blood_type)" placeholder="A+, O-, AB+" />
                <x-input-error class="mt-2" :messages="$errors->get('blood_type')" />
            </div>

            <div>
                <x-input-label for="doctor_name" :value="__('Doctor Name')" />
                <x-text-input id="doctor_name" name="doctor_name" type="text" class="mt-1 block w-full" :value="old('doctor_name', optional($student->healthProfile)->doctor_name)" />
                <x-input-error class="mt-2" :messages="$errors->get('doctor_name')" />
            </div>

            <div>
                <x-input-label for="doctor_phone" :value="__('Doctor Phone')" />
                <x-text-input id="doctor_phone" name="doctor_phone" type="text" class="mt-1 block w-full" :value="old('doctor_phone', optional($student->healthProfile)->doctor_phone)" />
                <x-input-error class="mt-2" :messages="$errors->get('doctor_phone')" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="allergies" :value="__('Allergies')" />
                <textarea id="allergies" name="allergies" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('allergies', optional($student->healthProfile)->allergies) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('allergies')" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="medical_conditions" :value="__('Medical Conditions')" />
                <textarea id="medical_conditions" name="medical_conditions" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('medical_conditions', optional($student->healthProfile)->medical_conditions) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('medical_conditions')" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="medications" :value="__('Medications')" />
                <textarea id="medications" name="medications" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('medications', optional($student->healthProfile)->medications) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('medications')" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="emergency_medical_note" :value="__('Emergency Medical Note')" />
                <textarea id="emergency_medical_note" name="emergency_medical_note" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('emergency_medical_note', optional($student->healthProfile)->emergency_medical_note) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('emergency_medical_note')" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="health_remark" :value="__('Health Status Remark')" />
                <textarea id="health_remark" name="health_remark" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('health_remark', optional($student->healthProfile)->health_remark) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('health_remark')" />
            </div>
        </div>
    </section>

    <div class="flex items-center gap-3 border-t border-slate-200 pt-6">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('students.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
