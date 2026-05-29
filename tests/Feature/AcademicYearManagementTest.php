<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_academic_year_pages(): void
    {
        $response = $this->get(route('academic-years.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_academic_year_list(): void
    {
        $user = User::factory()->create();
        AcademicYear::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('academic-years.index'));

        $response->assertOk();
        $response->assertSee('Academic Years');
    }

    public function test_authenticated_users_can_create_an_academic_year(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('academic-years.store'), [
            'name' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'status' => 'active',
            'is_current' => '1',
        ]);

        $response->assertRedirect(route('academic-years.index'));

        $this->assertDatabaseHas('academic_years', [
            'name' => '2026-2027',
            'status' => 'active',
            'is_current' => 1,
        ]);
    }

    public function test_only_one_academic_year_can_be_current(): void
    {
        $user = User::factory()->create();
        $firstYear = AcademicYear::factory()->create([
            'name' => '2025-2026',
            'is_current' => true,
        ]);

        $this->actingAs($user)->post(route('academic-years.store'), [
            'name' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'status' => 'active',
            'is_current' => '1',
        ])->assertRedirect(route('academic-years.index'));

        $this->assertFalse($firstYear->fresh()->is_current);
        $this->assertDatabaseHas('academic_years', [
            'name' => '2026-2027',
            'is_current' => 1,
        ]);
    }

    public function test_authenticated_users_can_update_an_academic_year(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create([
            'name' => '2026-2027',
            'status' => 'draft',
            'is_current' => false,
        ]);

        $response = $this->actingAs($user)->patch(route('academic-years.update', $academicYear), [
            'name' => '2026-2027 Revised',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'status' => 'active',
            'is_current' => '1',
        ]);

        $response->assertRedirect(route('academic-years.index'));

        $this->assertDatabaseHas('academic_years', [
            'id' => $academicYear->id,
            'name' => '2026-2027 Revised',
            'status' => 'active',
            'is_current' => 1,
        ]);
    }

    public function test_authenticated_users_can_close_an_academic_year(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $response = $this->actingAs($user)->delete(route('academic-years.destroy', $academicYear));

        $response->assertRedirect(route('academic-years.index'));
        $this->assertDatabaseHas('academic_years', [
            'id' => $academicYear->id,
            'status' => 'closed',
            'is_current' => 0,
        ]);
    }
}
