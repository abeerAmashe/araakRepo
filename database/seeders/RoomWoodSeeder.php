<?php

namespace Database\Seeders;

use App\Models\RoomWood;
use Illuminate\Database\Seeder;

class RoomWoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // إنشاء روابط بين الغرف والأخشاب بشكل عشوائي
        RoomWood::factory()->count(50)->create(); // يمكنك تحديد العدد الذي ترغب فيه
    }
}