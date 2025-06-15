<?php

namespace App\Http\Controllers;

use App\Models\Customization;
use App\Models\Fabric;
use App\Models\Item;
use App\Models\Wood;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{

    public function getItemDetails($itemId)
    {
        $item = Item::with([
            'itemDetail.itemWoods.wood.colors',
            'itemDetail.itemWoods.wood.types',
            'itemDetail.itemFabrics.fabric.colors',
            'itemDetail.itemFabrics.fabric.types',
            'ratings.customer'
        ])->where('id', $itemId)->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found']);
        }

        $averageRating = (float) $item->ratings()->avg('rate');

        $ratings = $item->ratings->map(function ($rating) {
            return [
                'feedback' => $rating->feedback,
                'rate' => (float) $rating->rate,
                'customer' => [
                    'id' => $rating->customer->id,
                    'name' => $rating->customer->name,
                    'image_url' => $rating->customer->image_url ?? null,
                ],
            ];
        });

        $userId = auth()->id();
        $customer = $userId ? \App\Models\Customer::where('user_id', $userId)->first() : null;

        $customerId = $customer ? $customer->id : null;

        // Debug:
        // dd($customerId, $itemId);

        $isLiked = $customerId
            ? \App\Models\Like::where('item_id', $itemId)->where('customer_id', $customerId)->exists()
            : false;

        $isFavorite = $customerId
            ? \App\Models\Favorite::where('item_id', $itemId)->where('customer_id', $customerId)->exists()
            : false;

        $likeCounts = \App\Models\Like::where('item_id', $itemId)->count();

        $response = [
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'image_url' => $item->image_url,
                'description' => $item->description,
                'price' => $item->price,
                'time' => $item->time,
                'count' => $item->count,
                'count_reserved' => $item->count_reserved,
                'wood_color' => $item->wood_color,
                'wood_type' => $item->wood_type,
                'fabric_color' => $item->fabric_color,
                'fabric_type' => $item->fabric_type,
            ],

            'item_details' => $item->itemDetail,
            'average_rating' => (float) round($averageRating, 2),
            'ratings' => $ratings,
            'is_liked' => $isLiked,
            'is_favorite' => $isFavorite,
            'like_counts' => $likeCounts,
        ];

        return response()->json($response);
    }

    public function trendingItems()
    {
        $items = Item::with('room')
            ->withCount(['purchaseOrder as total_sales' => function ($query) {
                $query->select(DB::raw("SUM(item_orders.count)"));
            }])
            ->orderByDesc('total_sales')
            ->take(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items
        ]);
    }

    public function customizeItem(Request $request, Item $item)
    {

        $user = auth()->user()->customer;

        if (!$item) {
            return response()->json(['message' => 'item not found'], 200);
        }

        $itemDetail = $item->itemDetail;
        if (!$itemDetail) {
            return response()->json(['message' => 'item detail not found'], 200);
        }

        $existingCustomization = Customization::where('item_id', $item->id)
            ->where('customer_id', $user->id)
            ->first();

        $oldWood = Wood::find($itemDetail->wood_id);
        $newWood = $request->wood_id ? Wood::find($request->wood_id) : null;

        if ($request->wood_id && !$newWood) {
            return response()->json(['message' => 'wood type not found'], 200);
        }

        $oldFabric = Fabric::find($itemDetail->fabric_id);
        $newFabric = $request->fabric_id ? Fabric::find($request->fabric_id) : null;

        if ($request->fabric_id && !$newFabric) {
            return response()->json(['message' => 'fabric type not found'], 200);
        }

        $new_wood_Color = $request->wood_color ?? null;
        $new_fabric_Color = $request->fabric_color ?? null;

        $woodArea = 2 * ($itemDetail->wood_length * $itemDetail->wood_width
            + $itemDetail->wood_length * $itemDetail->wood_height
            + $itemDetail->wood_width * $itemDetail->wood_height);

        $woodAreaM2 = $woodArea / 10_000;

        $oldWoodPrice = $oldWood ? $woodAreaM2 * $oldWood->price_per_meter : 0;
        $newWoodPrice = $newWood ? $woodAreaM2 * $newWood->price_per_meter : 0;

        $oldFabricPrice = $oldFabric ? ($itemDetail->fabric_dimension) * $oldFabric->price_per_meter : 0;
        $newFabricPrice = $newFabric ? ($itemDetail->fabric_dimension) * $newFabric->price_per_meter : 0;

        $extraLength = $request->add_to_length ?? 0;
        $extraWidth = $request->add_to_width ?? 0;
        $extraHeight = $request->add_to_height ?? 0;

        $extraWoodCost = ($extraLength + $extraWidth + $extraHeight) * 0.1 * ($newWood ? $newWood->price_per_meter : $oldWood->price_per_meter);
        $extraFabricCost = ($extraLength + $extraWidth + $extraHeight) * 0.1 * ($newFabric ? $newFabric->price_per_meter : $oldFabric->price_per_meter);

        $finalPrice = $item->price;
        if ($newWood) {
            $finalPrice = $finalPrice - $oldWoodPrice + $newWoodPrice;
        }

        if ($newFabric) {
            $finalPrice = $finalPrice - $oldFabricPrice + $newFabricPrice;
        }

        $finalPrice = $finalPrice + $extraWoodCost + $extraFabricCost;

        $originalTime = $itemDetail->time;
        $finalTime = $originalTime + 5;

        if ($existingCustomization) {
            $existingCustomization->update([
                'wood_id' => $newWood ? $newWood->id : $existingCustomization->wood_id,
                'fabric_id' => $newFabric ? $newFabric->id : $existingCustomization->fabric_id,
                'extra_length' => $extraLength,
                'extra_width' => $extraWidth,
                'extra_height' => $extraHeight,
                'final_price' => $finalPrice,
                'wood_color' => $new_wood_Color ?? $existingCustomization->wood_color,
                'fabric_color' => $new_fabric_Color ?? $existingCustomization->fabric_color,

            ]);

            $customization = $existingCustomization;
        } else {
            $customization = Customization::create([
                'item_id' => $item->id,
                'customer_id' => $user->id,
                'wood_id' => $newWood ? $newWood->id : null,
                'fabric_id' => $newFabric ? $newFabric->id : null,
                'extra_length' => $extraLength,
                'extra_width' => $extraWidth,
                'extra_height' => $extraHeight,
                'old_price' => $item->price,
                'final_price' => $finalPrice,
                'new_wood_color' => $new_wood_Color,
                'new_fabric_color' => $new_fabric_Color
            ]);
        }

        return response()->json([
            'message' => 'Done ^_^',
            'customization' => $customization,
            'final_time' => $finalTime,
            'customization_id' => $customization->id,
        ]);
    }
    public function getItemCustomization($itemId)
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'login is required!'], 200);
        }

        $item = Item::find($itemId);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 200);
        }

        $customization = Customization::where('item_id', $itemId)
            ->where('customer_id', $user->customer->id)
            ->first();

        if ($customization) {
            return response()->json([
                'message' => 'Customization found',
                'customization' => $customization,
            ], 200);
        } else {
            return response()->json([
                'message' => 'No customization found for this item',
            ], 200);
        }
    }

    // public function isItemCustomized($itemId)
    // {
    //     $user = auth()->user();

    //     if (!$user || !$user->customer) {
    //         return response()->json(['message' => 'login is required'], 200);
    //     }

    //     $customerId = $user->customer->id;

    //     $isCustomized = \App\Models\Customization::where('item_id', $itemId)
    //         ->where('customer_id', $customerId)
    //         ->exists();

    //     return $isCustomized;
    // }

    public function handleCustomizationResponse(Request $request, $itemId)
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'login is required!'], 200);
        }

        $customerId = $user->customer->id;

        $request->validate([
            'action' => 'required|string|in:accept,reject',
        ]);

        $customization = \App\Models\Customization::where('item_id', $itemId)
            ->where('customer_id', $customerId)
            ->first();

        if (!$customization) {
            return response()->json(['message' => 'there is no customize'], 200);
        }

        if ($request->action === 'accept') {
            return response()->json([
                'message' => 'Done ^_^',
                'customization' => $customization,
            ]);
        }

        if ($request->action === 'reject') {
            $customization->delete();

            return response()->json([
                'message' => 'Done ^_^',
            ]);
        }
    }
}