<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Item;
use App\Models\Room;
use App\Models\ItemType;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition()
    {
        return [
            'room_id' => Room::factory(),
            'name' => $this->faker->word(),
            'time' => $this->faker->numberBetween(10, 120),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'image_url' => $this->faker->imageUrl(),
            'description' => $this->faker->sentence(),
            'count' => $this->faker->numberBetween(1, 100),
            'count_reserved' => $this->faker->numberBetween(0, 10),
            'item_type_id' => ItemType::factory(),
        ];
    }
}