<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Room;
use App\Models\ItemType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'time' => $this->faker->numberBetween(0, 60),
            'price' => $this->faker->randomFloat(2, 1, 1000),
            'count' => $this->faker->numberBetween(1, 100),
            'count_reserved' => $this->faker->numberBetween(0, 50),
            'image_url' => $this->faker->imageUrl(640, 480, 'products', true),
            'description' => $this->faker->sentence(10),
            'item_type_id' => ItemType::inRandomOrder()->first()?->id ?? ItemType::factory(),
            'room_id' => Room::inRandomOrder()->first()?->id ?? Room::factory(),
        ];
    }
}
