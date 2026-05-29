<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'staff_no' => ['required', 'string', 'max:50', 'unique:staff,staff_no'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'join_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'status' => ['required', 'in:active,inactive,on-leave,resigned'],
            'create_login_account' => ['nullable', 'boolean'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'username' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'username'),
            ],
            'user_email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'user_phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['nullable', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
            'assignment_academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'assigned_section_ids' => ['nullable', 'array'],
            'assigned_section_ids.*' => ['integer', 'exists:sections,id'],
        ];
    }

    public function staffData(array $validated): array
    {
        return [
            'staff_no' => $validated['staff_no'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'gender' => $validated['gender'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'department' => $validated['department'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'join_date' => $validated['join_date'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
        ];
    }

    public function shouldCreateUserAccount(array $validated): bool
    {
        return (bool) ($validated['create_login_account'] ?? false) || filled($validated['username'] ?? null);
    }

    public function userData(array $validated): array
    {
        return [
            'name' => ($validated['account_name'] ?? null) ?: trim($validated['first_name'] . ' ' . $validated['last_name']),
            'username' => $validated['username'] ?? null,
            'email' => $validated['user_email'] ?? null,
            'phone' => $validated['user_phone'] ?? ($validated['phone'] ?? null),
            'password' => $validated['password'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $validated = $this->safe()->toArray();

            if (! $this->shouldCreateUserAccount($validated)) {
                return;
            }

            if (blank($validated['username'] ?? null)) {
                $validator->errors()->add('username', 'A username is required when creating a login account.');
            }

            if (blank($validated['password'] ?? null)) {
                $validator->errors()->add('password', 'A password is required when creating a login account.');
            }
        });
    }
}
