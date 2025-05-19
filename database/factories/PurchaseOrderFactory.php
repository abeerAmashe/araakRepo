<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'customer_id'     => 1,
            'status'          => $this->faker->randomElement(['not_ready', 'ready', 'in_progress']),
            'delivery_status' => $this->faker->randomElement(['pending', 'negotiation', 'confirmed']),
            'is_recived'      => $this->faker->randomElement(['pending', 'done']),
            'want_delivery'   => $this->faker->randomElement(['yes', 'no']),
            'is_paid'         => $this->faker->randomElement(['pending', 'partial', 'paid']),
            'total_price' => $this->faker->randomFloat(2, 100, 1000), 
            'recive_date'     => Carbon::now()->addDays(3),
            'latitude'        => $this->faker->latitude,
            'longitude'       => $this->faker->longitude,
        ];
    }
}