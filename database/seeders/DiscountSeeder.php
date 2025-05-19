<?php

// database/seeders/DiscountSeeder.php
// database/seeders/DiscountSeeder.php

namespace Database\Seeders;

use App\Models\Discount;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run()
    {
        // يمكنك تخصيص عدد التخفيضات هنا
        Discount::factory()->count(10)->create();
    }
}