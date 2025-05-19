<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition()
    {
        return [
            'customer_id' => Customer::inRandomOrder()->first()?->id ?? Customer::factory(),
            'item_id' => Item::inRandomOrder()->first()?->id ?? Item::factory(),
            'room_id' => Room::inRandomOrder()->first()?->id ?? Room::factory(),
            'count' => $this->faker->numberBetween(1, 5),
            'time_per_item' => $this->faker->numberBetween(10, 60),
            'price_per_item' => $this->faker->randomFloat(2, 10, 100),
            'customization_id' => null,
            'room_customization_id' => null,
            'time' => 0,
            'price' => 0,
            'available_count_at_addition' => $this->faker->numberBetween(1, 20),
            'reserved_at' => $this->faker->dateTimeBetween('now', '+7 days'), 
        ];
    }
}