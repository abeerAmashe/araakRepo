<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseOrder;
use Illuminate\Support\Carbon;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        PurchaseOrder::factory()->count(5)->create();

        PurchaseOrder::create([
            'customer_id'     => 1,
            'status'          => 'not_ready',        // بدل is_ready
            'delivery_status' => 'pending',          // جديد
            'is_recived'      => 'pending',          // enum بدل false
            'want_delivery'   => 'yes',              // enum بدل true
            'is_paid'         => 'pending',          // enum بدل boolean
            'total_price'     => 600.00,
            'recive_date'     => Carbon::now()->addDays(4),
            'latitude'        => 33.5138,
            'longitude'       => 36.2765,
        ]);
        
    }
}