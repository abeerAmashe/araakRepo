<?php

// namespace Database\Seeders;

// // use Illuminate\Database\Console\Seeds\WithoutModelEvents;

// use App\Models\RoomDetail;
// use Illuminate\Database\Seeder;

// class DatabaseSeeder extends Seeder
// {
//     /**
//      * Seed the application's database.
//      */
//     public function run(): void
//     {
//         $this->call([
//             UserSeeder::class,
//             CustomerSeeder::class,
//             CategorySeeder::class,
//             RoomSeeder::class,
//             RoomDetailSeeder::class,
//             WoodSeeder::class,
//             FabricSeeder::class,
//             ColorSeeder::class,
//          nnnn   TypeSeeder::class,
// ItemSeeder::class,
// FavoriteSeeder::class,
// TypeSeeder::class,
// ItemDetailSeeder::class,
// AvailableTimeSeeder::class,
// DeliveryCompanyAvailabilitySeeder::class,
// PurchaseOrderSeeder::class,
// AvailableTimeSeeder::class,
// RatingSeeder::class,
// LikeSeeder::class,
// DiscountSeeder::class,
// RoomWoodSeeder::class,
// RoomFabricSeeder::class,

//         ]);
//     }
// }





namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Room;
use App\Models\RoomDetail;
use App\Models\Wood;
use App\Models\Fabric;
use App\Models\Color;
use App\Models\Type;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // استدعاء باقي الـ seeders
        $this->call([
            UserSeeder::class,
            CustomerSeeder::class,
            CategorySeeder::class,
            RoomSeeder::class,
            RoomDetailSeeder::class,
            // حذف الاستدعاء المنفصل لـ Wood / Fabric / Color / Type لأنه رح نعملهم هون
            // WoodSeeder::class,
            // FabricSeeder::class,
            // ColorSeeder::class,
            // TypeSeeder::class,
            LikeSeeder::class,
            RatingSeeder::class,
            ItemSeeder::class,
            //

        ]);

        // إنشاء عينات من الأخشاب والأقمشة
        $woods = Wood::factory()->count(10)->create();
        $fabrics = Fabric::factory()->count(10)->create();

        // لكل Wood: أضف لون ونوع
        foreach ($woods as $wood) {
            // إضافة لون مرتبط بالـ Wood فقط
            Color::factory()->create([
                'name' => fake()->safeColorName(),
                'wood_id' => $wood->id,
                'fabric_id' => null,
            ]);

            // إضافة نوع مرتبط بـ Wood و Fabric عشوائي
            Type::factory()->create([
                'wood_id' => $wood->id,
                'fabric_id' => $fabrics->random()->id,
            ]);
        }

        // لكل Fabric: أضف لون ونوع
        foreach ($fabrics as $fabric) {
            // إضافة لون مرتبط بالـ Fabric فقط
            Color::factory()->create([
                'name' => fake()->safeColorName(),
                'fabric_id' => $fabric->id,
                'wood_id' => null,
            ]);

            // إضافة نوع مرتبط بـ Fabric و Wood عشوائي
            Type::factory()->create([
                'fabric_id' => $fabric->id,
                'wood_id' => $woods->random()->id,
            ]);
        }
    }
}
