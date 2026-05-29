<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\Room;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_section_pages(): void
    {
        $response = $this->get(route('sections.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_section_list(): void
    {
        $user = User::factory()->create();
        Section::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('sections.index'));

        $response->assertOk();
        $response->assertSee('Sections');
    }

    public function test_authenticated_users_can_create_a_section(): void
    {
        $user = User::factory()->create();
        $grade = Grade::factory()->create();
        $room = Room::factory()->create();

        $response = $this->actingAs($user)->post(route('sections.store'), [
            'grade_id' => $grade->id,
            'room_id' => $room->id,
            'name' => 'A',
            'code' => 'A',
            'capacity' => 40,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('sections.index'));

        $this->assertDatabaseHas('sections', [
            'grade_id' => $grade->id,
            'room_id' => $room->id,
            'name' => 'A',
            'code' => 'A',
            'capacity' => 40,
            'status' => 'active',
        ]);
    }

    public function test_section_code_must_be_unique_within_the_same_grade(): void
    {
        $user = User::factory()->create();
        $grade = Grade::factory()->create();
        Section::factory()->create([
            'grade_id' => $grade->id,
            'code' => 'A',
        ]);

        $response = $this->actingAs($user)->from(route('sections.create'))->post(route('sections.store'), [
            'grade_id' => $grade->id,
            'name' => 'Alpha',
            'code' => 'A',
            'capacity' => 30,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('sections.create'));
        $response->assertSessionHasErrors('code');
    }

    public function test_section_code_can_repeat_in_different_grades(): void
    {
        $user = User::factory()->create();
        $firstGrade = Grade::factory()->create();
        $secondGrade = Grade::factory()->create();

        Section::factory()->create([
            'grade_id' => $firstGrade->id,
            'code' => 'A',
        ]);

        $response = $this->actingAs($user)->post(route('sections.store'), [
            'grade_id' => $secondGrade->id,
            'name' => 'A',
            'code' => 'A',
            'capacity' => 35,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('sections.index'));
        $this->assertDatabaseHas('sections', [
            'grade_id' => $secondGrade->id,
            'code' => 'A',
        ]);
    }

    public function test_authenticated_users_can_update_a_section(): void
    {
        $user = User::factory()->create();
        $grade = Grade::factory()->create();
        $room = Room::factory()->create();
        $section = Section::factory()->create([
            'grade_id' => $grade->id,
            'name' => 'A',
            'code' => 'A',
        ]);

        $response = $this->actingAs($user)->patch(route('sections.update', $section), [
            'grade_id' => $grade->id,
            'room_id' => $room->id,
            'name' => 'Section A',
            'code' => 'A1',
            'capacity' => 45,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('sections.index'));

        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'room_id' => $room->id,
            'name' => 'Section A',
            'code' => 'A1',
            'capacity' => 45,
        ]);
    }

    public function test_section_can_be_created_without_assigned_room(): void
    {
        $user = User::factory()->create();
        $grade = Grade::factory()->create();

        $response = $this->actingAs($user)->post(route('sections.store'), [
            'grade_id' => $grade->id,
            'name' => 'B',
            'code' => 'B',
            'capacity' => 35,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('sections.index'));
        $this->assertDatabaseHas('sections', [
            'grade_id' => $grade->id,
            'code' => 'B',
            'room_id' => null,
        ]);
    }

    public function test_authenticated_users_can_close_a_section(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create();

        $response = $this->actingAs($user)->delete(route('sections.destroy', $section));

        $response->assertRedirect(route('sections.index'));
        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'status' => 'closed',
        ]);
    }
}
