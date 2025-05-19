<?php


// // database/factories/RoomFactory.php

// namespace Database\Factories;

// use App\Models\Room;
// use App\Models\Category;
// use Illuminate\Database\Eloquent\Factories\Factory;

// class RoomFactory extends Factory
// {
//     protected $model = Room::class;

//     public function definition()
//     {
//         return [
//             'name' => $this->faker->word, // اسم الغرفة عشوائي
//             'category_id' => Category::inRandomOrder()->first()->id, // فئة عشوائية
//             'description' => $this->faker->sentence, // وصف عشوائي
//             'price' => $this->faker->randomFloat(2, 100, 1000), // سعر عشوائي
//             'image_url' => $this->faker->imageUrl(), // صورة عشوائية
//         ];
//     }

//     public function configure()
//     {
//         return $this->afterCreating(function (Room $room) {
//             \App\Models\Item::factory()->count(random_int(2, 5))->create(['room_id' => $room->id]); // إنشاء عناصر عشوائية مرتبطة بالغرفة
//         });
//     }
// }


// database/factories/RoomFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category;

class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'description' => $this->faker->paragraph,
            'image_url' => $this->faker->imageUrl(),
            'count_reserved' => $this->faker->numberBetween(0, 10),
            'time' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->randomFloat(2, 100, 1000),
            'count' => $this->faker->numberBetween(1, 50),

            // الحقول المضافة
            'wood_type' => $this->faker->randomElement(['Oak', 'Pine', 'Walnut', 'Mahogany']),
            'wood_color' => $this->faker->safeColorName(),
            'fabric_type' => $this->faker->randomElement(['Cotton', 'Linen', 'Silk', 'Polyester']),
            'fabric_color' => $this->faker->safeColorName(),
        ];
    }
}