<?php


namespace Database\Factories;

use App\Models\DeliveryCompanyAvailability;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryCompanyAvailabilityFactory extends Factory
{
    protected $model = DeliveryCompanyAvailability::class;

    public function definition()
    {
        return [
            'day_of_week' => $this->faker->dayOfWeek,
            'start_time' => $this->faker->time, // إضافة الوقت بداية
            'end_time' => $this->faker->time,   // إضافة الوقت نهاية
        ];
    }
}