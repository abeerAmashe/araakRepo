<?php

namespace Database\Factories;

use App\Models\AvailableTime;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailableTimeFactory extends Factory
{
    protected $model = AvailableTime::class;

    public function definition()
    {
        return [
            'customer_id' => Customer::inRandomOrder()->first()?->id ?? Customer::factory(),
            'available_at' => $this->faker->dateTimeBetween('+1 days', '+1 week'),
        ];
    }
}