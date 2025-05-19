<?php

namespace Database\Seeders;

use App\Models\RoomFabric;
use Illuminate\Database\Seeder;

class RoomFabricSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // إنشاء روابط بين الغرف والأقمشة بشكل عشوائي
        RoomFabric::factory()->count(50)->create(); // يمكنك تحديد العدد الذي ترغب فيه
    }
}