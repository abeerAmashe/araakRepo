<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;
use App\Models\Item;

class LikeFactory extends Factory
{
    protected $model = \App\Models\Like::class;

    public function definition()
    {
        return [
            'customer_id' => Customer::factory(),
            'item_id' => Item::factory(),
            'room_id' => null, // not relevant here
        ];
    }
}