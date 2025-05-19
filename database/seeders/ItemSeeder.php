<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\Favorite;
use App\Models\Like;
use App\Models\Rating;
use Illuminate\Support\Facades\DB;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        // تعطيل قيود المفتاح الخارجي مؤقتًا لضمان القدرة على التفرغ بشكل صحيح
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // تأكد من مسح الجداول القديمة قبل إضافة بيانات جديدة (اختياري)
        Item::truncate(); 
        Favorite::truncate(); 
        Like::truncate();
        Rating::truncate();

        // إعادة تفعيل القيود بعد الحذف
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // إنشاء 10 عناصر باستخدام الـ Factory
        Item::factory()->count(10)->create()->each(function ($item) {
            // إضافة بيانات العُملاء المفضلة (favorites) لعناصر معينة
            Favorite::factory()->count(2)->create([
                'item_id' => $item->id,
            ]);

            // إضافة بيانات الإعجابات (likes) لعناصر معينة
            Like::factory()->count(3)->create([
                'item_id' => $item->id,
            ]);

            // إضافة بيانات التقييمات (ratings) لعناصر معينة
            Rating::factory()->count(5)->create([
                'item_id' => $item->id,
            ]);
        });
    }
}