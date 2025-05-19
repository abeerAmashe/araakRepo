<?php

namespace Database\Factories;

use App\Models\Like;
use App\Models\Customer;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class LikeFactory extends Factory
{
    protected $model = Like::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::inRandomOrder()->first()?->id ?? 1,
            'item_id' => null,
            'room_id' => Room::inRandomOrder()->first()?->id ?? null,
        ];
    }
}