<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\ItemDetail;
use App\Models\Wood;
use App\Models\Fabric;
use App\Models\Type;
use App\Models\Like;
use App\Models\Rating;
use App\Models\Customer;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $woods = Wood::all();
        $fabrics = Fabric::all();
        $types = Type::all();
        $customers = Customer::all();

        // إنشاء 10 عناصر
        Item::factory(10)->create()->each(function ($item) use ($woods, $fabrics, $types, $customers) {
            // إنشاء تفاصيل العنصر
            ItemDetail::create([
                'item_id' => $item->id,
                'wood_id' => $woods->random()->id,
                'fabric_id' => $fabrics->random()->id,
                'wood_length' => rand(100, 300),
                'wood_width' => rand(50, 150),
                'wood_height' => rand(30, 120),
                'fabric_dimension' => rand(100, 200),
                'wood_color' => fake()->safeColorName(),
                'fabric_color' => fake()->safeColorName(),
            ]);

            // إضافة لايكات وتقييمات واختيارات مفضلة
            $randomCustomers = $customers->random(rand(1, 3));
            foreach ($randomCustomers as $customer) {
                Like::factory()->create([
                    'customer_id' => $customer->id,
                    'item_id' => $item->id,
                    'room_id' => null,
                ]);

                Rating::factory()->create([
                    'customer_id' => $customer->id,
                    'item_id' => $item->id,
                    'room_id' => null,
                    'rate' => rand(1, 5),
                    'feedback' => fake()->optional()->sentence(),
                ]);

                $customer->favorites()->create([
                    'item_id' => $item->id,
                    'room_id' => null,
                ]);
            }
        });
    }
}