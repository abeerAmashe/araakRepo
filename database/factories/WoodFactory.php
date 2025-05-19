<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WoodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'price_per_meter' => $this->faker->randomFloat(2, 20, 100),
            // هان لازم تحط room_detail_id مرتبط بروم ديتيل موجود أو جديد
            // 'room_detail_id' => \App\Models\RoomDetail::factory(),
        ];
    }
}