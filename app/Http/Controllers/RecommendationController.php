<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\Favorite;
use App\Models\Item;
use App\Models\Like;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function recommend(Request $request)
    {
        $customerId = auth()->user()->customer->id;

        $favoriteRoomIds = Favorite::where('customer_id', $customerId)->pluck('room_id')->filter()->unique();
        $likedRoomIds = Like::where('customer_id', $customerId)->pluck('room_id')->filter()->unique();

        $favoriteItemRoomIds = Favorite::where('customer_id', $customerId)
            ->whereNotNull('item_id')->with('item.room')->get()
            ->pluck('item.room.id')->filter()->unique();

        $likedItemRoomIds = Like::where('customer_id', $customerId)
            ->whereNotNull('item_id')->with('item.room')->get()
            ->pluck('item.room.id')->filter()->unique();

        $allRoomIds = $favoriteRoomIds
            ->merge($likedRoomIds)
            ->merge($favoriteItemRoomIds)
            ->merge($likedItemRoomIds)
            ->unique();

        $categoryCounts = Room::whereIn('id', $allRoomIds)
            ->get()
            ->groupBy('category_id')
            ->map(fn($rooms) => count($rooms))
            ->sortDesc();

        if ($categoryCounts->isEmpty()) {
            return response()->json([
                'recommended_items' => [],
                'recommended_rooms' => [],
                'category_counts' => [],
            ]);
        }

        $topCategoryIds = $categoryCounts->keys()->take(10);

        $excludedItemIds = Favorite::where('customer_id', $customerId)->pluck('item_id')
            ->merge(Like::where('customer_id', $customerId)->pluck('item_id'))->unique()->filter();

        $recommendedItems = Item::whereHas('room', fn($q) => $q->whereIn('category_id', $topCategoryIds))
            ->whereNotIn('id', $excludedItemIds)
            ->with('room')
            ->withAvg('ratings', 'rate')
            ->take(10)
            ->get()
            ->map(function ($item) {
                $data = $item->toArray();
                $data['category_id'] = $item->room->category_id ?? null;
                $data['average_rating'] = (float) round($item->ratings_avg_rate, 2);

                if (isset($data['room']['price'])) {
                    $data['room']['price'] = floatval($data['room']['price']);
                }
                return $data;
            });

        $recommendedRooms = Room::whereIn('category_id', $topCategoryIds)
            ->with('items')
            ->withAvg('ratings', 'rate')
            ->take(10)
            ->get()
            ->map(function ($room) {
                $data = $room->toArray();
                $data['category_id'] = $room->category_id;
                $data['average_rating'] = (float) round($room->ratings_avg_rate, 2);
                if (isset($data['price'])) {
                    $data['price'] = floatval($data['price']);
                }

                return $data;
            });

        return response()->json([
            'recommended_items' => $recommendedItems,
            'recommended_rooms' => $recommendedRooms,
            'category_counts' => $categoryCounts,
        ]);
    }
    public function getTrending()
    {
        $now = Carbon::now();

        $trendingItems = Item::withCount('likes')
            ->orderByDesc('likes_count')
            ->take(5)
            ->get();

        $trendingRooms = Room::withCount('likes')
            ->orderByDesc('likes_count')
            ->take(5)
            ->get();

        $activeDiscounts = Discount::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->with(['room', 'item'])
            ->get();

        $roomDiscounts = [];
        $itemDiscounts = [];

        foreach ($activeDiscounts as $discount) {
            $isRoomDiscount = $discount->room_id !== null;
            $isItemDiscount = $discount->item_id !== null;

            $model = $isRoomDiscount ? $discount->room : $discount->item;
            $originalPrice = isset($model?->price) ? (float) $model->price : null;
            $imageUrl = $model?->image_url ?? null;
            $name = $model?->name ?? null;

            $discountData = [
                'id' => $discount->id,
                'room_id' => $discount->room_id,
                'item_id' => $discount->item_id,
                'discount_percentage' => $discount->discount_percentage,
                'start_date' => $discount->start_date,
                'end_date' => $discount->end_date,
                'original_price' => $originalPrice,
                'discounted_price' => $originalPrice !== null
                    ? (float) round($originalPrice * (1 - $discount->discount_percentage / 100), 2)
                    : null,

                'image_url' => $imageUrl,
                'name' => $name,
            ];

            if ($isRoomDiscount) {
                $roomDiscounts[] = $discountData;
            } elseif ($isItemDiscount) {
                $itemDiscounts[] = $discountData;
            }
        }

        return response()->json([
            'message' => 'Trending items and rooms',
            'trending_items' => $trendingItems,
            'trending_rooms' => $trendingRooms,
            'room_discounts' => $roomDiscounts,
            'item_discounts' => $itemDiscounts,
        ], 200);
    }
}