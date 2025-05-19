<?php

namespace Database\Seeders;

// database/seeders/FixRoomRelationsSeeder.php

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\RoomDetail;
use App\Models\Wood;
use App\Models\Fabric;
use App\Models\Type;
use App\Models\Color;

class FixRoomRelationsSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = Room::all();

        foreach ($rooms as $room) {
            // إذا ما عنده room_detail، أنشئ واحد
            $roomDetail = $room->roomDetails()->first();
            if (!$roomDetail) {
                $roomDetail = RoomDetail::create(['room_id' => $room->id]);
            }

            // Wood مرتبط بالroom_detail
            if ($roomDetail->woods()->count() === 0) {
                $wood = Wood::create([
                    'name' => 'Oak',
                    'color' => 'Brown',
                    'price_per_meter' => 120,
                    'room_detail_id' => $roomDetail->id,
                ]);

                Type::create([
                    'name' => 'Hardwood',
                    'wood_id' => $wood->id,
                ]);

                Color::create([
                    'name' => 'Dark Walnut',
                    'wood_id' => $wood->id,
                ]);
            }

            // Fabric مرتبط بالroom_detail
            if ($roomDetail->fabrics()->count() === 0) {
                $fabric = Fabric::create([
                    'name' => 'Linen',
                    'price_per_meter' => 80,
                    'room_detail_id' => $roomDetail->id,
                ]);

                Type::create([
                    'name' => 'Soft',
                    'fabric_id' => $fabric->id,
                ]);

                Color::create([
                    'name' => 'Beige',
                    'fabric_id' => $fabric->id,
                ]);
            }
        }
    }
}