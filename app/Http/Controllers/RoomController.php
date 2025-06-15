<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CustomizationItem;
use App\Models\Fabric;
use App\Models\Item;
use App\Models\Rating;
use App\Models\Room;
use App\Models\RoomCustomization;
use App\Models\Wood;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    public function showFurniture()
    {
        $response = Room::with(['items' => function ($q) {
            $q->withCount('likes')->withAvg('ratings', 'rate');
        }])
            ->withCount('likes')
            ->withAvg('ratings', 'rate')
            ->get()
            ->map(function ($room) {
                return [
                    'id' => $room->id,
                    'name' => $room->name,
                    'time' => (float)$room->items->sum('time'),
                    'price' => (float)$room->price,
                    'description' => $room->description,
                    'image_url' => $room->image_url,
                    'like_count' => $room->likes_count,
                    'average_rating' => (float) round($room->ratings_avg_rate ?? 0, 2),
                    'items' => $room->items->map(function ($item) {
                        $itemDetail = $item->itemDetail->first();
                        $wood = $itemDetail ? Wood::find($itemDetail->wood_id) : null;
                        $fabric = $itemDetail ? Fabric::find($itemDetail->fabric_id) : null;

                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'time' => (float)$item->time,
                            'price' => (float)$item->price,
                            'image_url' => $item->image_url,
                            'wood_id' => optional($wood)->id,
                            'wood_name' => optional($wood)->name,
                            'wood_color' => optional($wood)->color,
                            'wood_price_per_meter' => (float)optional($wood)->price_per_meter,
                            'fabric_id' => optional($fabric)->id,
                            'fabric_name' => optional($fabric)->name,
                            'fabric_color' => optional($fabric)->color,
                            'fabric_price_per_meter' => (float)optional($fabric)->price_per_meter,
                        ];
                    }),
                ];
            });

        return response()->json(['allRooms' => $response], 200);
    }
    public function getRoomsByCategory($category_id)
    {
        $category = Category::with('rooms')->find($category_id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 200);
        }

        return response()->json([
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'rooms' => $category->rooms->map(function ($room) {
                    return [
                        'id' => $room->id,
                        'name' => $room->name,
                        'description' => $room->description,
                        'price' => $room->price,
                        'image_url' => $room->image_url,
                        'likes_count' => $room->likes()->count(),
                        'average_rating' => (float) round($room->ratings()->avg('rate'), 1),
                        'feedbacks' => $room->ratings->pluck('feedback')->filter()->values(),
                    ];
                })
            ]
        ], 200);
    }
    public function getRoomItems($room_id)
    {
        $room = Room::with([
            'items.itemDetail',
            'items.likes',
            'items.ratings'
        ])->find($room_id);

        if (!$room) {
            return response()->json(['message' => 'room not found'], 200);
        }

        return response()->json([
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'description' => $room->description,
                'price' => $room->price,
                'items' => $room->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'time' => $item->time,
                        'wood_id' => optional($item->itemDetail)->wood_id,
                        'fabric_id' => optional($item->itemDetail)->fabric_id,
                        'wood_length' => optional($item->itemDetail)->wood_length,
                        'wood_width' => optional($item->itemDetail)->wood_width,
                        'wood_height' => optional($item->itemDetail)->wood_height,
                        'likes_count' => $item->likes->count(),
                        'average_rating' => (float) round($item->ratings->avg('rate'), 1),
                        'feedbacks' => $item->ratings->pluck('feedback')->filter()->values()
                    ];
                })
            ]
        ], 200);
    }
    public function getRoomDetails(Request $request, $room_id)
    {
        $customer = auth()->user()?->customer;

        $room = Room::with([
            'category',
            'items',
            'roomDetails.woods.types',
            'roomDetails.woods.colors',
            'roomDetails.fabrics.types',
            'roomDetails.fabrics.colors'
        ])->find($room_id);

        if (!$room) {
            return response()->json(['message' => 'Room not found'], 200);
        }

        $isFavorite = false;
        $isLiked = false;

        if ($customer) {
            $isFavorite = $customer->favorites()->where('room_id', $room->id)->exists();
            $isLiked = $customer->likes()->where('room_id', $room->id)->exists();
        }

        $allWoods = collect();
        $allFabrics = collect();

        foreach ($room->roomDetails as $detail) {
            $allWoods = $allWoods->merge(
                $detail->woods->map(function ($wood) {
                    return [
                        'id' => $wood->id,
                        'name' => $wood->name,
                        'price_per_meter' => $wood->price_per_meter,
                        'types' => $wood->types->map(fn($type) => [
                            'id' => $type->id,
                            'wood_id' => $type->wood_id,
                            'fabric_id' => $type->fabric_id,
                            'name' => $type->name ?? null,
                        ]),
                        'colors' => $wood->colors->map(fn($color) => [
                            'id' => $color->id,
                            'wood_id' => $color->wood_id,
                            'fabric_id' => $color->fabric_id,
                            'name' => $color->name,
                        ]),
                    ];
                })
            );

            $allFabrics = $allFabrics->merge(
                $detail->fabrics->map(function ($fabric) {
                    return [
                        'id' => $fabric->id,
                        'name' => $fabric->name,
                        'price_per_meter' => $fabric->price_per_meter,
                        'types' => $fabric->types->map(fn($type) => [
                            'id' => $type->id,
                            'name' => $type->name ?? null,
                            'wood_id' => $type->wood_id,
                            'fabric_id' => $type->fabric_id,
                        ]),
                        'colors' => $fabric->colors ? $fabric->colors->map(fn($color) => [
                            'id' => $color->id,
                            'wood_id' => $color->wood_id,
                            'fabric_id' => $color->fabric_id,
                            'name' => $color->name,
                        ]) : [],
                    ];
                })
            );
        }

        $allWoods = $allWoods->unique('id')->values();
        $allFabrics = $allFabrics->unique('id')->values();

        $ratings = Rating::with('customer.user')
            ->where('room_id', $room->id)
            ->get();

        $ratingData = $ratings->map(function ($rating) {
            return [
                'customer_name' => $rating->customer?->user?->name,
                'customer_image' => $rating->customer?->profile_image,
                'rate' => (float) $rating->rate,
                'feedback' => $rating->feedback,
            ];
        });

        $averageRating = (float) $ratings->avg('rate');
        $totalRate = $ratings->count();

        return response()->json([
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'category_id' => $room->category_id,
                'category_name' => $room->category?->name,
                'description' => $room->description,
                'image_url' => $room->image_url,
                'count_reserved' => $room->count_reserved,
                'time' => $room->time,
                'price' => $room->price,
                'count' => $room->count,
                'is_favorite' => $isFavorite,
                'is_liked' => $isLiked,
                'likes_count' => $room->likes()->count(),
                'average_rating' => (float) $averageRating,
                'total_rate' => $totalRate,
                'items' => $room->items->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'image_url' => $item->image_url,
                ]),
            ],
            'woods' => $allWoods,
            'fabrics' => $allFabrics,
            'ratings' => $ratingData,
        ]);
    }    
    public function trendingRooms()
    {
        $rooms = Room::withCount(['roomOrder as total_sales' => function ($query) {
            $query->select(DB::raw("SUM(room_orders.count)"));
        }])
            ->orderByDesc('total_sales')
            ->take(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $rooms
        ]);
    }
    public function getRoomAfterCustomization($roomCustomizationId)
    {
        $baseCustomization = RoomCustomization::with('room.items')->find($roomCustomizationId);

        if (!$baseCustomization) {
            return response()->json(['message' => 'Room customization not found'], 200);
        }

        $room = $baseCustomization->room;

        $roomCustomizations = RoomCustomization::with('customizationItems.item')
            ->where('room_id', $room->id)
            ->get();

        if ($roomCustomizations->isEmpty()) {
            return response()->json(['message' => 'No customizations found for this room'], 200);
        }

        $customizationsData = $roomCustomizations->map(function ($customization) use ($room) {
            $itemsData = $room->items->map(function ($roomItem) use ($customization) {
                $customized = $customization->customizationItems->firstWhere('item_id', $roomItem->id);

                return [
                    'item_id' => $roomItem->id,
                    'item_name' => $roomItem->name,
                    'customized' => $customized ? true : false,
                    'final_price' => $customized ? $customized->final_price : $roomItem->price,
                    'final_time' => $customized ? $customized->final_time : $roomItem->time,
                ];
            });

            return [
                'room_customization_id' => $customization->id,
                'final_price' => $customization->final_price,
                'final_time' => $customization->final_time,
                'items' => $itemsData,
            ];
        });

        return response()->json([
            'message' => 'All room customizations retrieved successfully!',
            'room_id' => $room->id,
            'room_name' => $room->name,
            'customizations' => $customizationsData,
        ]);
    }
     public function customizeRoom(Request $request, $roomId)
    {
        $user = auth()->user()->customer;

        $room = Room::find($roomId);
        if (!$room) {
            return response()->json(['message' => 'Room not found'], 200);
        }

        if (!$request->has('items')) {
            return response()->json(['message' => 'Items data is required'], 200);
        }

        $itemsData = $request->input('items');
        $totalRoomPrice = 0;
        $totalRoomTime = 0;
        $itemPrices = [];
        $customizedItemIds = [];

        $roomCustomization = RoomCustomization::create([
            'room_id' => $roomId,
            'customer_id' => $user->id,
            'final_price' => 0,
            'final_time' => 0,
        ]);

        foreach ($itemsData as $customizationData) {
            $item = Item::find($customizationData['item_id']);
            if (!$item) {
                return response()->json(['message' => 'Item not found'], 200);
            }

            $finalPrice = $item->price;
            $finalTime = $item->time ?? 0;

            $newWood = isset($customizationData['wood_id']) ? Wood::find($customizationData['wood_id']) : null;
            $newFabric = isset($customizationData['fabric_id']) ? Fabric::find($customizationData['fabric_id']) : null;

            $extraLength = $customizationData['add_to_length'] ?? 0;
            $extraWidth = $customizationData['add_to_width'] ?? 0;
            $extraHeight = $customizationData['add_to_height'] ?? 0;

            $woodArea = 2 * (
                $item->itemDetail->wood_length * $item->itemDetail->wood_width +
                $item->itemDetail->wood_length * $item->itemDetail->wood_height +
                $item->itemDetail->wood_width * $item->itemDetail->wood_height
            );
            $woodAreaM2 = $woodArea / 10000;

            $newWoodPrice = $newWood ? $woodAreaM2 * $newWood->price_per_meter : 0;
            $newFabricPrice = $newFabric ? $item->itemDetail->fabric_dimension * $newFabric->price_per_meter : 0;

            $extraWoodCost = ($extraLength + $extraWidth + $extraHeight) * 0.1 * ($newWood ? $newWood->price_per_meter : 0);
            $extraFabricCost = ($extraLength + $extraWidth + $extraHeight) * 0.1 * ($newFabric ? $newFabric->price_per_meter : 0);

            $finalPrice += $newWoodPrice + $newFabricPrice + $extraWoodCost + $extraFabricCost;

            CustomizationItem::create([
                'room_customization_id' => $roomCustomization->id,
                'item_id' => $item->id,
                'wood_id' => $newWood?->id,
                'fabric_id' => $newFabric?->id,
                'wood_color' => $customizationData['wood_color'] ?? null,
                'fabric_color' => $customizationData['fabric_color'] ?? null,
                'add_to_length' => $extraLength,
                'add_to_width' => $extraWidth,
                'add_to_height' => $extraHeight,
                'final_price' => $finalPrice,
                'final_time' => $finalTime,
            ]);

            $itemPrices[] = [
                'item_id' => $item->id,
                'final_price' => number_format($finalPrice, 2, '.', ''),
                'customized' => true,
                'time' => $finalTime,
            ];

            $customizedItemIds[] = $item->id;

            $totalRoomPrice += $finalPrice;
            $totalRoomTime += $finalTime;
        }

        $roomItems = $room->items;

        foreach ($roomItems as $item) {
            if (!in_array($item->id, $customizedItemIds)) {
                $totalRoomTime += $item->time ?? 0;
                $totalRoomPrice += $item->price ?? 0;

                $itemPrices[] = [
                    'item_id' => $item->id,
                    'final_price' => number_format($item->price, 2, '.', ''),
                    'customized' => false,
                    'time' => $item->time ?? 0,
                ];
            }
        }

        $roomCustomization->update([
            'final_price' => number_format($totalRoomPrice, 2, '.', ''),
            'final_time' => $totalRoomTime + 10,
        ]);

        return response()->json([
            'message' => 'Room customized successfully!',
            'item_prices' => $itemPrices,
            'total_room_price' => number_format($totalRoomPrice, 2, '.', ''),
            'total_room_time' => $totalRoomTime + 10,
        ]);
    }

    // public function getRoomCustomization($roomId)
    // {
    //     $user = auth()->user();
    //     if (!$user || !$user->customer) {
    //         return response()->json(['message' => 'login is required!'], 200);
    //     }

    //     $customerId = $user->customer->id;

    //     $roomCustomization = RoomCustomization::where('room_id', $roomId)
    //         ->where('customer_id', $customerId)
    //         ->with('customizedItems.item', 'customizedItems.customization')
    //         ->first();

    //     $room = Room::with('items')->find($roomId);

    //     if (!$room) {
    //         return response()->json(['message' => 'Room not found'], 200);
    //     }

    //     $items = $room->items->map(function ($item) use ($roomCustomization) {
    //         $customizedItem = optional($roomCustomization)->customizedItems
    //             ->where('item_id', $item->id)
    //             ->first();

    //         return [
    //             'item' => $item,
    //             'is_customized' => $customizedItem ? true : false,
    //             'customization' => $customizedItem ? $customizedItem->customization : null,
    //         ];
    //     });

    //     return response()->json([
    //         'room_id' => $room->id,
    //         'room_name' => $room->name,
    //         'items' => $items,
    //     ]);
    // }


    
}