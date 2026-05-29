<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        $name = 'Room ' . fake()->unique()->numberBetween(101, 999);

        return [
            'name' => $name,
            'code' => fake()->unique()->bothify('RM-###'),
            'building' => fake()->randomElement(['Main Building', 'Primary Block', 'Secondary Block']),
            'floor' => (string) fake()->numberBetween(1, 4),
            'capacity' => fake()->randomElement([20, 25, 30, 35, 40]),
            'room_type' => fake()->randomElement(array_keys(Room::typeOptions())),
            'status' => fake()->randomElement(array_keys(Room::statusOptions())),
        ];
    }
}
