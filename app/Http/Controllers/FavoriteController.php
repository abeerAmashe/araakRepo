<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Item;
use App\Models\Like;
use App\Models\Room;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggleFavorite(Request $request)
    {
        $user = auth()->user();
        $customer = $user->customer;

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 200);
        }

        if ($request->has('room_id')) {
            $room_id = $request->room_id;
            $favorite = Favorite::where('customer_id', $customer->id)
                ->where('room_id', $room_id)
                ->first();

            if ($favorite) {
                $favorite->delete();
                return response()->json(['message' => 'Room removed from favorites successfully'], 200);
            } else {
                $favorite = new Favorite();
                $favorite->customer_id = $customer->id;
                $favorite->room_id = $room_id;
                $favorite->save();
                return response()->json(['message' => 'Room added to favorites successfully'], 200);
            }
        } elseif ($request->has('item_id')) {
            $item_id = $request->item_id;
            $favorite = Favorite::where('customer_id', $customer->id)
                ->where('item_id', $item_id)
                ->first();

            if ($favorite) {
                $favorite->delete();
                return response()->json(['message' => 'Item removed from favorites successfully'], 200);
            } else {
                $favorite = new Favorite();
                $favorite->customer_id = $customer->id;
                $favorite->item_id = $item_id;
                $favorite->save();
                return response()->json(['message' => 'Item added to favorites successfully'], 200);
            }
        } else {
            return response()->json(['message' => 'No item or room provided'], 200);
        }
    }
    public function getFavoritesWithDetails()
    {
        $user = auth()->user();
        $favorites = $user->customer->favorites()->get();

        $roomIds = $favorites->whereNotNull('room_id')->pluck('room_id')->toArray();
        $itemIds = $favorites->whereNotNull('item_id')->pluck('item_id')->toArray();

        $rooms = Room::with(['items' => function ($query) use ($itemIds) {
            $query->whereIn('id', $itemIds)->with('itemDetail');
        }])->whereIn('id', $roomIds)->get();

        $items = Item::with('itemDetail')->whereIn('id', $itemIds)->get();

        $rooms = $rooms->map(function ($room) {
            $room->likes_count = $room->likes()->count();
            $room->total_rating = (float)round($room->ratings()->avg('rate') ?? 0, 1);

            if ($room->relationLoaded('items')) {
                $room->items = $room->items->map(function ($item) {
                    $item->is_favorite = true;
                    $item->likes_count = $item->likes()->count();
                    $item->total_rating = (float)round($item->ratings()->avg('rate') ?? 0, 1);
                    return $item;
                });
            }

            return $room;
        });

        $items = $items->map(function ($item) {
            $item->is_favorite = true;
            $item->likes_count = $item->likes()->count();
            $item->total_rating = (float)round($item->ratings()->avg('rate') ?? 0, 1);
            return $item;
        });

        return response()->json([
            'rooms' => $rooms,
            'items' => $items,
        ], 200);
    }
    public function toggleLike(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'login is required'], 200);
        }

        $request->validate([
            'item_id' => 'nullable|integer|exists:items,id',
            'room_id' => 'nullable|integer|exists:rooms,id',
        ]);

        if (!$request->item_id && !$request->room_id) {
            return response()->json(['message' => 'يجب إرسال item_id أو room_id.'], 200);
        }

        $like = Like::where('customer_id', $user->customer->id)
            ->where('item_id', $request->item_id)
            ->where('room_id', $request->room_id)
            ->first();

        if ($like) {
            $like->delete();

            return response()->json(['message' => 'unliked'], 200);
        }

        Like::create([
            'customer_id' => $user->customer->id,
            'item_id' => $request->item_id,
            'room_id' => $request->room_id,
        ]);

        return response()->json(['message' => 'liked'], 201);
    }
    public function getCustomerLikes()
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'login is required'], 200);
        }

        $likes = Like::where('customer_id', $user->customer->id)->get();

        $itemsLikes = $likes->whereNotNull('item_id')->map(function ($like) {
            $item = Item::where('id', $like->item_id)->get();
            return $item;
            return [
                'item_id' => $like->item_id,
                'name' => $item->name,
                'time' => $item->time,
                'price' => $item->price,
                'liked_at' => $like->created_at->toDateTimeString(),
            ];
        });

        $roomsLikes = $likes->whereNotNull('room_id')->map(function ($like) {
            $room = Room::where('id', $like->room_id)->first();
            // return  $room->name;
            // return $room;
            return [
                'room_id' => $like->room_id,
                'room_name' => $room->name,
                'room_category_id' => Category::where('id', $room->category_id)->first()->id,
                'room_category_name' => Category::where('id', $room->category_id)->first()->name,
                'room_description' => $room->description,
                'room_image' => $room->image_url,
                'liked_at' => $like->created_at->toDateTimeString(),
            ];
        });
        return response()->json([
            'message' => 'Done',
            'items_likes' => $itemsLikes,
            'rooms_likes' => $roomsLikes,
        ], 200);
    }
    public function getCustomerFavorites()
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'Customer not found'], 200);
        }

        $favorites = Favorite::where('customer_id', $user->customer->id)->get();

        $roomsFavorites = $favorites->whereNotNull('room_id')->map(function ($favorite) {
            return [
                'room_id' => $favorite->room_id,
                'added_at' => $favorite->created_at->toDateTimeString(),
            ];
        });

        $itemsFavorites = $favorites->whereNotNull('item_id')->map(function ($favorite) {
            return [
                'item_id' => $favorite->item_id,
                'added_at' => $favorite->created_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'message' => 'Customer favorites retrieved successfully',
            'rooms' => $roomsFavorites,
            'items' => $itemsFavorites,
        ], 200);
    }
}
