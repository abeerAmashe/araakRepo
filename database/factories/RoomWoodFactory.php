<?php

namespace Database\Factories;

use App\Models\RoomWood;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomWoodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'room_id' => \App\Models\Room::inRandomOrder()->first()->id, // ربط الغرفة بشكل عشوائي
            'wood_id' => \App\Models\Wood::inRandomOrder()->first()->id, // ربط الخشب بشكل عشوائي
        ];
    }
}
