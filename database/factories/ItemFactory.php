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
        $images = [
            'download.jpeg',
            'OIP.jpeg',
            'OIP1.jpeg',
            'OIP2.jpeg',
            'OIP3.jpeg',
            'OIP4.jpeg',
            'th.jpeg',
        ];

        return [
            'room_id' => Room::factory(),
            'name' => $this->faker->word(),
            'time' => $this->faker->numberBetween(10, 120),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'image_url' => 'images/' . $this->faker->randomElement($images),
            'description' => $this->faker->sentence(),
            'wood_color' => $this->faker->safeColorName(),
            'wood_type' => $this->faker->word(),
            'fabric_type' => $this->faker->word(),
            'fabric_color' => $this->faker->safeColorName(),
            'count' => $this->faker->numberBetween(1, 100),
            'count_reserved' => $this->faker->numberBetween(0, 10),
            'item_type_id' => ItemType::factory(),
        ];
    }
}
