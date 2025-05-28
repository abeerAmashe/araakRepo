<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('123456789'),
        ];
    }

    public function configure()
{
    return $this->afterCreating(function (User $user) {
        $user->wallets()->create([
            'balance' => 0.00,
            'currency' => 'usd',
            'wallet_type' => 'investment',
            'is_active' => true,
        ]);
    });
}

}