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
use App\Models\ItemWood;
use App\Models\ItemFabric;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $woods = Wood::all();
        $fabrics = Fabric::all();
        $customers = Customer::all();

        Item::factory(10)->create()->each(function ($item) use ($woods, $fabrics, $customers) {
            // إنشاء تفاصيل العنصر بدون wood_id و fabric_id
            $itemDetail = ItemDetail::create([
                'item_id' => $item->id,
                'wood_length' => rand(100, 300),
                'wood_width' => rand(50, 150),
                'wood_height' => rand(30, 120),
                'fabric_dimension' => rand(100, 200),
                // 'wood_color' => fake()->safeColorName(),
                // 'fabric_color' => fake()->safeColorName(),
            ]);

            // ربط Woods مع ItemDetail
            $wood = $woods->random();
            ItemWood::create([
                'item_detail_id' => $itemDetail->id,
                // إضافة أي حقول إضافية إذا موجودة في الجدول
            ]);

            // ربط Fabrics مع ItemDetail
            $fabric = $fabrics->random();
            ItemFabric::create([
                'item_detail_id' => $itemDetail->id,
                // إضافة أي حقول إضافية إذا موجودة في الجدول
            ]);

            // إضافة لايكات وتقييمات ومفضلات
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