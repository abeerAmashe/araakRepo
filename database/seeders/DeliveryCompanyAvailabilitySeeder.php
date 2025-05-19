<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeliveryCompanyAvailability;

class DeliveryCompanyAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($days as $day) {
            DeliveryCompanyAvailability::create([
                'day_of_week' => $day,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
            ]);
        }
    }
}