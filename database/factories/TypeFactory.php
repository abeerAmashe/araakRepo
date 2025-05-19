<?php

namespace Database\Factories;

use App\Models\Type;
use App\Models\Wood;
use App\Models\Fabric;
use Illuminate\Database\Eloquent\Factories\Factory;

class TypeFactory extends Factory
{
    protected $model = Type::class;

    public function definition(): array
    {
        $typeNames = [
            'Single Chair',
            'Small Sofa',
            'Large Sofa',
            'Side Table',
            'Center Table',
            'Bed',
            'Wardrobe',
            'Desk',
            'Bookshelf',
            'Wall Shelf',
        ];

        return [
            'name' => $this->faker->randomElement($typeNames),
            'wood_id' => Wood::factory(),
            'fabric_id' => Fabric::factory(),
        ];
    }
}