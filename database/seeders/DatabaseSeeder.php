<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wood;
use App\Models\Fabric;
use App\Models\Type;
use App\Models\Color;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // إنشاء عينات من الأخشاب والأقمشة أولاً
        $woods = Wood::factory()->count(10)->create();
        $fabrics = Fabric::factory()->count(10)->create();

        // ربط كل Wood بلون ونوع
        foreach ($woods as $wood) {
            Color::factory()->create([
                'name' => fake()->safeColorName(),
                'wood_id' => $wood->id,
                'fabric_id' => null,
            ]);

            Type::factory()->create([
                'wood_id' => $wood->id,
                'fabric_id' => $fabrics->random()->id,
            ]);
        }

        // ربط كل Fabric بلون ونوع
        foreach ($fabrics as $fabric) {
            Color::factory()->create([
                'name' => fake()->safeColorName(),
                'fabric_id' => $fabric->id,
                'wood_id' => null,
            ]);

            Type::factory()->create([
                'fabric_id' => $fabric->id,
                'wood_id' => $woods->random()->id,
            ]);
        }

        $this->call([
            UserSeeder::class,
            CustomerSeeder::class,
            CategorySeeder::class,
            RoomSeeder::class,
            RoomDetailSeeder::class,
            LikeSeeder::class,
            RatingSeeder::class,
            ItemSeeder::class,

            FixRoomRelationsSeeder::class,
            CartSeeder::class,
            DiscountSeeder::class,

        ]);
    }
}