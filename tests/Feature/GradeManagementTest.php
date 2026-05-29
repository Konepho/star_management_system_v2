<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_grade_pages(): void
    {
        $response = $this->get(route('grades.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_grade_list(): void
    {
        $user = User::factory()->create();
        Grade::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('grades.index'));

        $response->assertOk();
        $response->assertSee('Grades');
    }

    public function test_authenticated_users_can_create_a_grade(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('grades.store'), [
            'name' => 'Grade 1',
            'code' => 'G1',
            'grade_group' => 'primary',
            'sort_order' => 1,
            'remarks' => 'Primary entry level',
        ]);

        $response->assertRedirect(route('grades.index'));

        $this->assertDatabaseHas('grades', [
            'name' => 'Grade 1',
            'code' => 'G1',
            'grade_group' => 'primary',
            'sort_order' => 1,
        ]);
    }

    public function test_grade_can_be_created_for_pearson_igcse_group(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('grades.store'), [
            'name' => 'IGCSE 1',
            'code' => 'IG1',
            'grade_group' => Grade::GROUP_PEARSON_IGCSE,
            'sort_order' => 13,
            'remarks' => 'Pearson pathway',
        ]);

        $response->assertRedirect(route('grades.index'));

        $this->assertDatabaseHas('grades', [
            'name' => 'IGCSE 1',
            'grade_group' => Grade::GROUP_PEARSON_IGCSE,
        ]);
    }

    public function test_grade_code_must_be_unique(): void
    {
        $user = User::factory()->create();
        Grade::factory()->create(['code' => 'G1']);

        $response = $this->actingAs($user)->from(route('grades.create'))->post(route('grades.store'), [
            'name' => 'Grade 1 Duplicate',
            'code' => 'G1',
            'grade_group' => 'primary',
            'sort_order' => 2,
            'remarks' => null,
        ]);

        $response->assertRedirect(route('grades.create'));
        $response->assertSessionHasErrors('code');
    }

    public function test_authenticated_users_can_update_a_grade(): void
    {
        $user = User::factory()->create();
        $grade = Grade::factory()->create([
            'name' => 'Grade 1',
            'code' => 'G1',
        ]);

        $response = $this->actingAs($user)->patch(route('grades.update', $grade), [
            'name' => 'Grade 1 Updated',
            'code' => 'G1A',
            'grade_group' => 'secondary',
            'sort_order' => 3,
            'remarks' => 'Updated remarks',
        ]);

        $response->assertRedirect(route('grades.index'));

        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'name' => 'Grade 1 Updated',
            'code' => 'G1A',
            'grade_group' => 'secondary',
            'sort_order' => 3,
        ]);
    }

    public function test_authenticated_users_can_delete_a_grade(): void
    {
        $user = User::factory()->create();
        $grade = Grade::factory()->create();

        $response = $this->actingAs($user)->delete(route('grades.destroy', $grade));

        $response->assertRedirect(route('grades.index'));
        $this->assertModelMissing($grade);
    }

    public function test_used_grade_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $grade = Grade::factory()->create();
        \App\Models\Section::factory()->create(['grade_id' => $grade->id]);

        $response = $this->actingAs($user)->delete(route('grades.destroy', $grade));

        $response->assertRedirect(route('grades.index'));
        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
        ]);
    }
}
