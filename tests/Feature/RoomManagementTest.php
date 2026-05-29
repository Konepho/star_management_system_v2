<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_room_pages(): void
    {
        $response = $this->get(route('rooms.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_room_list(): void
    {
        $user = User::factory()->create();
        Room::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('rooms.index'));

        $response->assertOk();
        $response->assertSee('Rooms');
    }

    public function test_authenticated_users_can_create_a_room(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('rooms.store'), [
            'name' => 'Room 101',
            'code' => 'RM-101',
            'building' => 'Main Building',
            'floor' => '1',
            'capacity' => 40,
            'room_type' => 'classroom',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('rooms.index'));

        $this->assertDatabaseHas('rooms', [
            'name' => 'Room 101',
            'code' => 'RM-101',
            'building' => 'Main Building',
            'floor' => '1',
            'capacity' => 40,
            'room_type' => 'classroom',
            'status' => 'active',
        ]);
    }

    public function test_room_code_must_be_unique(): void
    {
        $user = User::factory()->create();
        Room::factory()->create([
            'code' => 'RM-101',
        ]);

        $response = $this->actingAs($user)->from(route('rooms.create'))->post(route('rooms.store'), [
            'name' => 'Room 102',
            'code' => 'RM-101',
            'building' => 'Main Building',
            'floor' => '1',
            'capacity' => 30,
            'room_type' => 'classroom',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('rooms.create'));
        $response->assertSessionHasErrors('code');
    }

    public function test_authenticated_users_can_update_a_room(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'name' => 'Old Room',
            'code' => 'RM-200',
        ]);

        $response = $this->actingAs($user)->patch(route('rooms.update', $room), [
            'name' => 'Science Lab',
            'code' => 'LAB-1',
            'building' => 'Secondary Block',
            'floor' => '2',
            'capacity' => 32,
            'room_type' => 'lab',
            'status' => 'maintenance',
        ]);

        $response->assertRedirect(route('rooms.index'));

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'name' => 'Science Lab',
            'code' => 'LAB-1',
            'room_type' => 'lab',
            'status' => 'maintenance',
        ]);
    }

    public function test_authenticated_users_can_deactivate_a_room(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $response = $this->actingAs($user)->delete(route('rooms.destroy', $room));

        $response->assertRedirect(route('rooms.index'));
        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => Room::STATUS_INACTIVE,
        ]);
    }
}
