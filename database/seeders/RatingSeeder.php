<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rating;
use App\Models\Room;
use App\Models\Customer;

class RatingSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();

        Room::all()->each(function ($room) use ($customers) {
            // نضيف مثلاً 3 تقييمات عشوائية لكل غرفة
            for ($i = 0; $i < 3; $i++) {
                Rating::factory()->create([
                    'room_id' => $room->id,
                    'customer_id' => $customers->random()->id,
                    'item_id' => null,
                ]);
            }
        });
    }
}