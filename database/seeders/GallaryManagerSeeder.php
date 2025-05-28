<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\GallaryManager;

class GallaryManagerSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->orderBy('id')->first();

        if (!$user) {
            $user = User::factory()->create();
        }

        if (!$user->customer) {
            GallaryManager::factory()->create([
                'user_id' => $user->id,
            ]);
        }
    }
}