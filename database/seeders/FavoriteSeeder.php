<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Favorite;
use App\Models\Room;
use App\Models\Customer;

class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        // مفضلات لعناصر
        Favorite::factory()->count(10)->create();

        // مفضلات لغرف
        $customers = Customer::all();
        $rooms = Room::all();

        foreach ($customers as $customer) {
            Favorite::create([
                'customer_id' => $customer->id,
                'room_id' => $rooms->random()->id,
                'item_id' => null,
            ]);
        }
    }
}