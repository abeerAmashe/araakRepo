<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $user= User::create([
            'name' => 'Abeer',
            'email' => 'abeer@example.com',
            'password' => Hash::make('password123'),
        ]);

        $user->wallets()->create([
            'balance' => 0.00,
            'currency' => 'usd',
            'wallet_type' => 'investment',
            'is_active' => true,
        ]);

        User::factory()->count(9)->create();
    }
}