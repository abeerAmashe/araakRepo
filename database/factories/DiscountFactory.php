<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Room;
use App\Models\Item;

class DiscountFactory extends Factory
{
    protected $model = \App\Models\Discount::class;

    public function definition()
    {
        // جلب room و item موجودين أو توليدهم
        $room = Room::inRandomOrder()->first() ?? Room::factory()->create();
        $item = Item::inRandomOrder()->first() ?? Item::factory()->create();

        $startDate = $this->faker->dateTimeBetween('-1 month', 'now');
        $endDate = $this->faker->dateTimeBetween($startDate, '+1 month');

        return [
            'room_id' => $room->id,
            'item_id' => $item->id,
            'discount_percentage' => $this->faker->numberBetween(5, 50),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }
}