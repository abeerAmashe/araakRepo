<?php

namespace Database\Factories;

use App\Models\Rating;
use App\Models\Customer;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::inRandomOrder()->first()?->id ?? 1,
            'room_id' => Room::inRandomOrder()->first()?->id ?? 1,
            'item_id' => null, // حاليًا نركّز على الغرف فقط، ممكن نعدله لاحقًا للعناصر
            'rate' => $this->faker->numberBetween(1, 5),
            'feedback' => $this->faker->sentence(12),
        ];
    }
}