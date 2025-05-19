<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ItemDetail;

class ItemDetailSeeder extends Seeder
{
    public function run(): void
    {
        // مسح البيانات السابقة (اختياري)
        ItemDetail::truncate();

        // إنشاء 10 سجلات باستخدام الـ Factory
        ItemDetail::factory()->count(10)->create();
    }
}