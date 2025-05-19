<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Room;

class RoomDetailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_id' => Room::inRandomOrder()->first()?->id ?? Room::factory(),
        ];
    }
}