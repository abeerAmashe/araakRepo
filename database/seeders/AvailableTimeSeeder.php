<?php

// database/seeders/AvailableTimeSeeder.php

namespace Database\Seeders;

use App\Models\AvailableTime;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class AvailableTimeSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();

        foreach ($customers as $customer) {
            AvailableTime::factory()->count(3)->create([
                'customer_id' => $customer->id,
            ]);
        }
    }
}
