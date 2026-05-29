<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_operational_dashboard_stats(): void
    {
        $user = User::factory()->create();

        AcademicYear::factory()->create([
            'name' => '2026-2027',
            'is_current' => true,
        ]);

        Student::factory()->count(3)->create([
            'status' => Student::STATUS_ACTIVE,
        ]);

        Staff::factory()->count(2)->create([
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Operations dashboard');
        $response->assertSee('Academic year: 2026-2027');
        $response->assertSee('Active Students');
        $response->assertSee('3');
        $response->assertSee('Active Staff');
        $response->assertSee('2');
        $response->assertSee('Quick Actions');
        $response->assertSee('Recent Activity');
    }

    public function test_teacher_dashboard_shows_academic_actions_without_finance_shortcuts(): void
    {
        $teacherRole = Role::query()->where('slug', 'teacher')->firstOrFail();

        $user = User::factory()->create();
        $user->roles()->sync([$teacherRole->id]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Teaching dashboard');
        $response->assertSee('Take Attendance');
        $response->assertSee('Enter Marks');
        $response->assertSee('Add Daily Report');
        $response->assertDontSee('Generate Invoice');
        $response->assertDontSee('Top Up Wallet');
    }
}
