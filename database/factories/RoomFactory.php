<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Room;
use App\Models\Category;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
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
        'name' => $this->faker->word,
        'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
        'description' => $this->faker->paragraph,
        'image_url' => 'images/' . $this->faker->randomElement($images),
        'count_reserved' => $this->faker->numberBetween(0, 10),
        'time' => $this->faker->numberBetween(1, 10),
        'price' => $this->faker->randomFloat(2, 100, 1000),
        'count' => $this->faker->numberBetween(1, 50),

        'wood_type' => $this->faker->randomElement(['Oak', 'Pine', 'Walnut', 'Mahogany']),
        'wood_color' => $this->faker->safeColorName(),
        'fabric_type' => $this->faker->randomElement(['Cotton', 'Linen', 'Silk', 'Polyester']),
        'fabric_color' => $this->faker->safeColorName(),
    ];
}

}