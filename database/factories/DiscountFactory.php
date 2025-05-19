<?php

// database/factories/DiscountFactory.php
// database/factories/DiscountFactory.php

namespace Database\Factories;

use App\Models\Discount;
use App\Models\Item;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition()
    {
        return [
            'room_id' => Room::inRandomOrder()->first()->id ?? null,
            'item_id' => Item::inRandomOrder()->first()->id ?? null,
            'discount_percentage' => $this->faker->numberBetween(5, 50),
            'start_date' => $this->faker->dateTimeBetween('now', '+1 week'),
            'end_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
        ];
    }
}