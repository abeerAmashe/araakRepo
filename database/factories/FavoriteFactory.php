<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Room;

class FavoriteFactory extends Factory
{
    protected $model = \App\Models\Favorite::class;

    public function definition()
    {
        $item = Item::inRandomOrder()->first() ?? Item::factory()->create();
        $room = $item->room ?? Room::inRandomOrder()->first() ?? Room::factory()->create();

        return [
            'customer_id' => Customer::inRandomOrder()->first()?->id ?? Customer::factory()->create()->id,
            'item_id' => $item->id,
            'room_id' => $room->id,
        ];
    }
}