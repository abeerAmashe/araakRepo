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
        return [
            'name' => $this->faker->word(),  // ✅ تمت الإضافة
            'wood_id' => Wood::factory(),
            'fabric_id' => Fabric::factory(),
        ];
    }
}