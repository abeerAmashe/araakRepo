<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;
use App\Models\Item;

class RatingFactory extends Factory
{
    protected $model = \App\Models\Rating::class;

    public function definition()
    {
        return [
            'customer_id' => Customer::factory(),
            'item_id' => Item::factory(),
            'room_id' => null,
            'rate' => $this->faker->numberBetween(1, 5),
            'feedback' => $this->faker->optional()->sentence(),
        ];
    }
}