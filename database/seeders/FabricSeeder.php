<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Fabric;

class FabricSeeder extends Seeder
{
    public function run(): void
    {
        Fabric::factory()->count(15)->create();
    }
}