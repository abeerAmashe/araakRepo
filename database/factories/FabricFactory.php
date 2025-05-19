<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FabricFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'price_per_meter' => $this->faker->randomFloat(2, 15, 80),
            // 'room_detail_id' => \App\Models\RoomDetail::factory(),
        ];
    }
}