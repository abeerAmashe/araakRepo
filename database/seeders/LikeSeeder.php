<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Like;
use App\Models\Customer;

class LikeSeeder extends Seeder
{
    public function run()
    {
        $customers = Customer::all();

        Room::all()->each(function ($room) use ($customers) {
            Like::factory()->create([
                'room_id' => $room->id,
                'customer_id' => $customers->random()->id,
                'item_id' => null,
            ]);
        });
    }
}