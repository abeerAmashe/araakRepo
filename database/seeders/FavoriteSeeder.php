<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Favorite;

class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        Favorite::factory()->count(20)->create();  // لتوليد 20 سجل مفضل
    }
}