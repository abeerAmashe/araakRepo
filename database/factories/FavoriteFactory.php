<?php

namespace Database\Factories;

use App\Models\Favorite;
use App\Models\Item;
use App\Models\Room;
use App\Models\Customer; // Assuming you have a Customer model
use Illuminate\Database\Eloquent\Factories\Factory;

class FavoriteFactory extends Factory
{
    protected $model = Favorite::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::inRandomOrder()->first()?->id ?? Customer::factory(), // Assuming you have a Customer factory
            'item_id' => Item::inRandomOrder()->first()?->id ?? Item::factory(),
            'room_id' => Room::inRandomOrder()->first()?->id ?? Room::factory(),
        ];
    }
}