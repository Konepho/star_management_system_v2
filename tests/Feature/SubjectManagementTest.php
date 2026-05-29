<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_subject_pages(): void
    {
        $response = $this->get(route('subjects.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_subject_list(): void
    {
        $user = User::factory()->create();
        Subject::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('subjects.index'));

        $response->assertOk();
        $response->assertSee('Subjects');
    }

    public function test_authenticated_users_can_create_a_subject(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('subjects.store'), [
            'name' => 'Mathematics',
            'code' => 'MATH',
            'description' => 'Core mathematics subject',
            'is_core' => '1',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('subjects.index'));

        $this->assertDatabaseHas('subjects', [
            'name' => 'Mathematics',
            'code' => 'MATH',
            'is_core' => 1,
            'status' => 'active',
        ]);
    }

    public function test_subject_code_must_be_unique(): void
    {
        $user = User::factory()->create();
        Subject::factory()->create(['code' => 'ENG']);

        $response = $this->actingAs($user)->from(route('subjects.create'))->post(route('subjects.store'), [
            'name' => 'English Duplicate',
            'code' => 'ENG',
            'description' => null,
            'is_core' => '1',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('subjects.create'));
        $response->assertSessionHasErrors('code');
    }

    public function test_authenticated_users_can_update_a_subject(): void
    {
        $user = User::factory()->create();
        $subject = Subject::factory()->create([
            'name' => 'Science',
            'code' => 'SCI',
        ]);

        $response = $this->actingAs($user)->patch(route('subjects.update', $subject), [
            'name' => 'General Science',
            'code' => 'SCI-GEN',
            'description' => 'Updated description',
            'is_core' => '0',
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('subjects.index'));

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'name' => 'General Science',
            'code' => 'SCI-GEN',
            'is_core' => 0,
            'status' => 'inactive',
        ]);
    }

    public function test_authenticated_users_can_deactivate_a_subject(): void
    {
        $user = User::factory()->create();
        $subject = Subject::factory()->create();

        $response = $this->actingAs($user)->delete(route('subjects.destroy', $subject));

        $response->assertRedirect(route('subjects.index'));
        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'status' => 'inactive',
        ]);
    }
}
