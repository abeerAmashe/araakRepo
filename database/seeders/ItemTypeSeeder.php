<?php


// database/seeders/ItemTypeSeeder.php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\ItemType;

class ItemTypeSeeder extends Seeder
{
    public function run()
    {
        $types = ['Chair', 'Table', 'Sofa', 'Bed', 'Lamp'];

        foreach ($types as $type) {
            ItemType::create([
                'name' => $type,
                'description' => fake()->sentence(),
            ]);
        }
    }
}
