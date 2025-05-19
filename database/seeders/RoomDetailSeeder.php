<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomDetail;
use App\Models\Wood;
use App\Models\Fabric;

class RoomDetailSeeder extends Seeder
{
    public function run()
    {
        RoomDetail::factory()->count(10)->create()->each(function ($roomDetail) {
            Wood::factory()->count(3)->create([
                'room_detail_id' => $roomDetail->id,
            ]);

            Fabric::factory()->count(3)->create([
                'room_detail_id' => $roomDetail->id,
            ]);
        });
    }
}