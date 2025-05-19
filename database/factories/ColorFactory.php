<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ColorFactory extends Factory
{
    public function definition(): array
    {
        $colors = ['Red', 'Blue', 'Green', 'Yellow', 'Black', 'White', 'Beige', 'Gray', 'Brown', 'Cream'];

        return [
            'name' => $this->faker->randomElement($colors),
            'wood_id' => null,
            'fabric_id' => null,
        ];
    }
}