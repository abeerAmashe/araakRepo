<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $categories = [
            'Kids Room',
            'Dining Room',
            'Living Room',
            'Bedroom',
            'Salon',
            'Office Furniture',
            'TV Units',
            'Wardrobes',
            'Bookshelves',
            'Entryway Furniture',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($categories),
        ];
    }
}