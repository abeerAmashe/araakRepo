<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Wood;
use App\Models\Fabric;
use App\Models\Item;

class ItemDetailFactory extends Factory
{
    protected $model = \App\Models\ItemDetail::class;

    public function definition()
    {
        return [
            'wood_id' => Wood::factory(),
            'item_id' => Item::factory(),
            'fabric_id' => Fabric::factory(),
            'wood_length' => $this->faker->randomFloat(2, 10, 100),
            'wood_width' => $this->faker->randomFloat(2, 10, 100),
            'wood_height' => $this->faker->randomFloat(2, 5, 50),
            'fabric_dimension' => $this->faker->numberBetween(10, 100),
           
        ];
    }
}