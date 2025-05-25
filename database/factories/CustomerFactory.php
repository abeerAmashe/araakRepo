<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition()
    {
        // تحديد مسار المجلد الذي يحتوي على الصور
        $profileImages = [
            'default.jpeg',
            'img1.JPG',
            'img2.JPG',
            'img3.JPG'
        ];

        $randomImage = $profileImages[array_rand($profileImages)];

        return [
            'user_id' => User::factory(),
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'phone_number' => $this->faker->unique()->phoneNumber,
            'profile_image' => 'profile/' . $randomImage,
            'address' => $this->faker->address,

        ];
    }
}
