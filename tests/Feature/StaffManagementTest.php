<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_staff_pages(): void
    {
        $this->get(route('staff.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_staff_list(): void
    {
        $user = User::factory()->create();
        Staff::factory()->create([
            'first_name' => 'Aye',
            'last_name' => 'Moe',
            'phone' => '0912345678',
        ]);
        Staff::factory()->create();

        $response = $this->actingAs($user)->get(route('staff.index'));

        $response->assertOk();
        $response->assertSee('Staff Management');
        $response->assertSee('Aye Moe');
        $response->assertSee('0912345678');
    }

    public function test_staff_list_prefers_user_name_when_legacy_staff_name_looks_like_phone_number(): void
    {
        $user = User::factory()->create();
        $staffUser = User::factory()->create(['name' => 'Daw Hnin Ei']);

        Staff::factory()->create([
            'user_id' => $staffUser->id,
            'first_name' => '0912345678',
            'last_name' => '',
            'phone' => '0912345678',
        ]);

        $response = $this->actingAs($user)->get(route('staff.index'));

        $response->assertOk();
        $response->assertSee('Daw Hnin Ei');
        $response->assertDontSee('<div class="font-medium text-slate-900">0912345678</div>', false);
    }

    public function test_staff_role_selection_only_shows_official_roles(): void
    {
        $user = User::factory()->create();

        Role::query()->create([
            'name' => 'HR Manager',
            'slug' => 'hr-manager',
            'description' => 'Legacy duplicate role',
            'is_system' => true,
        ]);

        $response = $this->actingAs($user)->get(route('staff.create'));

        $response->assertOk();
        $response->assertSee('HR Manager');
        $response->assertSee('Vice Principal');
        $response->assertSee('POS Cashier');
        $response->assertDontSee('System Administrator');
        $response->assertDontSee('Finance Officer');
        $response->assertDontSee('hr-manager');
    }

    public function test_authenticated_users_can_create_staff_without_login_account(): void
    {
        $user = User::factory()->create();
        Storage::fake('public');
        $photo = $this->fakeImageUpload('staff-photo.png');

        $response = $this->actingAs($user)->post(route('staff.store'), [
            'staff_no' => 'STF-0001',
            'first_name' => 'Aye',
            'last_name' => 'Moe',
            'gender' => 'female',
            'phone' => '0912345678',
            'email' => 'aye.staff@example.com',
            'department' => 'Academics',
            'designation' => 'Teacher',
            'join_date' => '2026-06-01',
            'address' => 'Yangon',
            'photo' => $photo,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('staff', [
            'staff_no' => 'STF-0001',
            'first_name' => 'Aye',
            'last_name' => 'Moe',
            'department' => 'Academics',
        ]);

        $staff = Staff::query()->where('staff_no', 'STF-0001')->firstOrFail();
        $this->assertNotNull($staff->photo_path);
        Storage::disk('public')->assertExists($staff->photo_path);
    }

    public function test_authenticated_users_can_create_staff_with_login_account_and_role(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', 'teacher')->firstOrFail();

        $response = $this->actingAs($user)->post(route('staff.store'), [
            'staff_no' => 'STF-0002',
            'first_name' => 'Ko',
            'last_name' => 'Min',
            'status' => 'active',
            'create_login_account' => '1',
            'username' => 'ko.min',
            'user_email' => 'ko.min@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'ko.min',
            'email' => 'ko.min@example.com',
        ]);

        $staff = Staff::query()->where('staff_no', 'STF-0002')->firstOrFail();

        $this->assertNotNull($staff->user_id);
        $this->assertSame($role->id, $staff->user->roles->first()?->id);
    }

    public function test_staff_number_must_be_unique(): void
    {
        $user = User::factory()->create();
        Staff::factory()->create(['staff_no' => 'STF-0099']);

        $response = $this->actingAs($user)
            ->from(route('staff.create'))
            ->post(route('staff.store'), [
                'staff_no' => 'STF-0099',
                'first_name' => 'Duplicate',
                'last_name' => 'Staff',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('staff.create'));
        $response->assertSessionHasErrors('staff_no');
    }

    public function test_username_must_be_unique_when_creating_staff_account(): void
    {
        $user = User::factory()->create(['username' => 'existing.user']);

        $response = $this->actingAs($user)
            ->from(route('staff.create'))
            ->post(route('staff.store'), [
                'staff_no' => 'STF-0100',
                'first_name' => 'New',
                'last_name' => 'Staff',
                'status' => 'active',
                'create_login_account' => '1',
                'username' => 'existing.user',
                'password' => 'password123',
            ]);

        $response->assertRedirect(route('staff.create'));
        $response->assertSessionHasErrors('username');
    }

    public function test_authenticated_users_can_update_a_staff_member(): void
    {
        $user = User::factory()->create();
        $staff = Staff::factory()->create();
        Storage::fake('public');
        $photo = $this->fakeImageUpload('updated-staff-photo.png');

        $response = $this->actingAs($user)->patch(route('staff.update', $staff), [
            'staff_no' => $staff->staff_no,
            'first_name' => 'Updated',
            'last_name' => 'Staff',
            'gender' => 'male',
            'phone' => '0999999999',
            'email' => 'updated.staff@example.com',
            'department' => 'Administration',
            'designation' => 'Coordinator',
            'join_date' => '2026-06-01',
            'address' => 'Mandalay',
            'photo' => $photo,
            'status' => 'on-leave',
        ]);

        $response->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'first_name' => 'Updated',
            'status' => 'on-leave',
        ]);

        $staff->refresh();
        $this->assertNotNull($staff->photo_path);
        Storage::disk('public')->assertExists($staff->photo_path);
    }

    public function test_authenticated_users_can_deactivate_a_staff_member(): void
    {
        $user = User::factory()->create();
        $staffUser = User::factory()->create([
            'is_active' => true,
        ]);
        $staff = Staff::factory()->create([
            'user_id' => $staffUser->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->delete(route('staff.destroy', $staff));

        $response->assertRedirect(route('staff.index'));
        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $staffUser->id,
            'is_active' => false,
        ]);
    }

    private function fakeImageUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aF9sAAAAASUVORK5CYII=')
        );
    }
}
