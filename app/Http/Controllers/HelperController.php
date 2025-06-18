<?php

namespace App\Http\Controllers;

use App\Models\AvailableTime;
use App\Models\Branch;
use App\Models\DeliveryCompanyAvailability;
use App\Models\Item;
use App\Models\PlaceCost;
use App\Models\PurchaseOrder;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HelperController extends Controller
{
    public function checkAvailableTime(Request $request)
    {
        $number = $request->input('count', 0);

        if ($request->has('room_id')) {
            $roomId = $request->room_id;

            $room = Room::find($roomId);

            if (!$room) {
                return response()->json(['message' => 'room not found'], 200);
            }

            $items = $room->items;
            $totalTime = 0;

            foreach ($items as $item) {
                if ($item->count < $number) {
                    $timePerItem = $item->time;
                    $missingCount = $number - $item->count;
                    $totalTime = max($totalTime, $timePerItem * $missingCount);
                }
            }

            if ($totalTime > 0) {
                return response()->json([
                    'message' => 'The room contains insufficient items.',
                    'required_time' => $totalTime
                ], 200);
            }

            return response()->json([
                'message' => 'All items in the room meet the required quantity.',
                'required_time' => 0
            ], 200);
        } elseif ($request->has('item_id')) {
            $itemId = $request->item_id;

            $item = Item::find($itemId);

            if (!$item) {
                return response()->json(['message' => 'item not found'], 200);
            }

            if ($item->count >= $number) {
                return response()->json([
                    'message' => 'The item is available and meets the required quantity.',
                    'item_count' => $item->count,
                    'required_time' => 0
                ], 200);
            } else {
                $timePerItem = $item->time;
                $missingCount = $number - $item->count;
                $requiredTime = $timePerItem * $missingCount;

                return response()->json([
                    'message' => 'The item does not meet the required quantity.',
                    'item_count' => $item->count,
                    'required_time' => $requiredTime
                ], 200);
            }
        } else {
            return response()->json(['message' => 'Please send either room_id or item_id.'], 200);
        }
    }
    protected function findAvailableDeliveryTime()
    {
        $customerId = auth()->user()->customer->id;

        $customerTimes = AvailableTime::where('customer_id', $customerId)
            ->pluck('available_at');

        $companyAvailability = DeliveryCompanyAvailability::get()
            ->keyBy('day_of_week');

        $bookedTimes = PurchaseOrder::whereNotNull('delivery_time')
            ->pluck('delivery_time')
            ->map(fn($time) => Carbon::parse($time)->format('Y-m-d H:i'))
            ->toArray();

        foreach ($customerTimes as $time) {
            $carbonTime = Carbon::parse($time);
            $formattedTime = $carbonTime->format('Y-m-d H:i');
            $dayName = strtolower($carbonTime->format('l'));

            if (!$companyAvailability->has($dayName)) {
                continue;
            }

            $startTime = $companyAvailability[$dayName]->start_time;
            $endTime = $companyAvailability[$dayName]->end_time;

            $timeOnly = $carbonTime->format('H:i:s');

            if ($timeOnly >= $startTime && $timeOnly <= $endTime) {
                if (!in_array($formattedTime, $bookedTimes)) {
                    return $formattedTime;
                }
            }
        }

        return null;
    }
   
    
    public function getExchangeRate($from, $to)
    {
        try {
            $url = "https://api.exchangerate-api.com/v4/latest/{$from}";
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            if (!isset($data['rates'][$to])) {
                throw new \Exception("Exchange rate not found");
            }

            return $data['rates'][$to];
        } catch (\Exception $e) {
            return 1;
        }
    }
}