<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wood;

class WoodSeeder extends Seeder
{
    public function run(): void
    {
        Wood::factory()->count(15)->create();
    }
}