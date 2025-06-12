<?php

namespace App\Http\Controllers;

use App\Models\AvailableTime;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\CustomerAvailableTime;
use App\Models\Customization;
use App\Models\CustomizationItem;
use App\Models\DeliveryCompanyAvailability;
use App\Models\DeliverySlot;
use App\Models\Discount;
use App\Models\Fabric;
use App\Models\Favorite;
use App\Models\GallaryManager;
use App\Models\Item;
use App\Models\ItemDetail;
use App\Models\ItemOrder;
use App\Models\ItemType;
use App\Models\Like;
use App\Models\PlaceCost;
use App\Models\PurchaseOrder;
use App\Models\Rating;
use App\Models\Room;
use App\Models\RoomCustomization;
use App\Models\RoomCustomizationItem;
use App\Models\RoomOrder;
use App\Models\StripePayment;
use App\Models\Transaction;
use App\Models\Wood;
use App\Models\WorkshopManagerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Testing\Fakes\Fake;
use Stripe\Forwarding\Request as ForwardingRequest;
use Stripe\Stripe;
// use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Wallet;






class CustomerController extends Controller
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

    public function getAllCategories()
    {
        $categories = Category::select('id', 'name')->distinct()->get();

        return response()->json(['categories' => $categories], 200);
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


    public function isItemCustomized($itemId)
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'login is required'], 200);
        }

        $customerId = $user->customer->id;

        $isCustomized = \App\Models\Customization::where('item_id', $itemId)
            ->where('customer_id', $customerId)
            ->exists();

        return $isCustomized;
    }


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

    public function viewCart(Request $request)
    {
        $user = auth()->user();

        $cartItems = Cart::where('customer_id', $user->customer->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'cart is empty'], 200);
        }

        $cartDetails = $cartItems->map(function ($item) {
            return [
                'cart_id' => $item->id,
                'item_id' => $item->item_id,
                'room_id' => $item->room_id,
                'count' => $item->count,
                'time_per_item' => $item->time_per_item,
                'price_per_item' => $item->price_per_item,
                'customization_id' => $item->customization_id,
            ];
        });

        return response()->json([
            'message' => 'cart Contents:',
            'cart_items' => $cartDetails,
        ], 200);
    }

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

    public function getCartDetails()
    {
        $customerId = auth()->user()->customer->id;

        $cartItems = \App\Models\Cart::with([
            'item',
            'room',
        ])
            ->where('customer_id', $customerId)
            ->get();

        $rooms = [];
        $items = [];
        $totalPrice = 0;
        $totalTime = 0;

        foreach ($cartItems as $cart) {
            // تأكد من تحويل السعر والوقت إلى أرقام
            $pricePerItem = (float) $cart->price_per_item;
            $timePerItem = (int) $cart->time_per_item;

            $lineTotalPrice = $pricePerItem * $cart->count;

            $totalPrice += $lineTotalPrice;
            $totalTime = max($totalTime, $timePerItem * $cart->count);

            if ($cart->room) {
                $rooms[] = [
                    'id' => $cart->room->id,
                    'name' => $cart->room->name,
                    'image_url' => $cart->room->image_url,
                    'price' => $pricePerItem,
                    'time' => $timePerItem,
                    'count' => $cart->count,
                ];
            }

            if ($cart->item) {
                $items[] = [
                    'id' => $cart->item->id,
                    'name' => $cart->item->name,
                    'image_url' => $cart->item->image_url,
                    'price' => $pricePerItem,
                    'count' => $cart->count,
                    'time' => $timePerItem,
                ];
            }
        }

        return response()->json([
            'rooms' => $rooms,
            'items' => $items,
            'total_price' => round($totalPrice, 2),
            'total_time' => $totalTime,
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

    public function submitComplaint(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'login is required'], 200);
        }

        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $complaint = Complaint::create([
            'customer_id' => $user->customer->id,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Done!',
            'complaint_id' => $complaint->id
        ], 201);
    }

    public function getCustomerComplaints()
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'login is required'], 200);
        }

        $complaints = Complaint::where('customer_id', $user->customer->id)->get();

        return response()->json([
            'message' => 'complaints:',
            'complaints' => $complaints
        ], 200);
    }

    public function addDeliveryAddress(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'Customer must be logged in'], 200);
        }

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $customer = $user->customer;

        $customer->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'message' => 'Delivery address has been successfully added.',
            'latitude' => $customer->latitude,
            'longitude' => $customer->longitude,


        ], 201);
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


    //الغرف والعناصر الاكثر مبيعا

    // العناصر التريندينغ
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

    // الغرف التريندينغ
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

    public function showProfile()
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'Customer not found'], 200);
        }

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'profile_image' => $user->customer->profile_image ?? null,
            'phone_number' => $user->customer->phone_number,
        ]);
    }

    // public function updateProfile(Request $request)
    // {
    //     $user = auth()->user();
    //     $customer = $user->customer;

    //     if (!$user || !$customer) {
    //         return response()->json(['message' => 'User not authenticated or not a customer'], 200);
    //     }

    //     $validator = Validator::make($request->all(), [
    //         'name' => 'sometimes|string|max:255',
    //         'profile_image' => 'sometimes|image|mimes:jpg,jpeg,png|max:5120',
    //         'current_password' => 'sometimes|required_with:new_password',
    //         'new_password' => 'sometimes|required_with:current_password|min:6|confirmed',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()]);
    //     }

    //     if ($request->has('name')) {
    //         $user->name = $request->name;
    //         $user->save();
    //     }

    //     if ($request->hasFile('profile_image')) {
    //         $image = $request->file('profile_image');
    //         $imageName = time() . '.' . $image->getClientOriginalExtension();

    //         $destinationPath = public_path('profile');
    //         $image->move($destinationPath, $imageName);

    //         $customer->profile_image = url('profile/' . $imageName);
    //         $customer->save();
    //     }

    //     if ($request->filled('current_password') && $request->filled('new_password')) {
    //         if (!Hash::check($request->current_password, $user->password)) {
    //             return response()->json(['message' => 'Current password is incorrect'], 200);
    //         }
    //         $user->password = Hash::make($request->new_password);
    //         $user->save();
    //     }

    //     return response()->json([
    //         'message' => 'Profile updated successfully',
    //         'name' => $user->name,
    //         'profile_image' => $customer->profile_image,
    //     ]);
    // }



    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $customer = $user->customer;

        if (!$user || !$customer) {
            return response()->json(['message' => 'User not authenticated or not a customer'], 200);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20',
            'profile_image' => 'sometimes|image|mimes:jpg,jpeg,png|max:5120',
            'current_password' => 'sometimes|required_with:new_password',
            'new_password' => 'sometimes|required_with:current_password|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        if ($request->has('name')) {
            $user->name = $request->name;
            $user->save();
        }

        if ($request->has('phone_number')) {
            $customer->phone_number = $request->phone_number;
            $customer->save();
        }

        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();

            $destinationPath = public_path('profile');
            $image->move($destinationPath, $imageName);

            $customer->profile_image = url('profile/' . $imageName);
            $customer->save();
        }

        if ($request->filled('current_password') && $request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect'], 200);
            }
            $user->password = Hash::make($request->new_password);
            $user->save();
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'name' => $user->name,
            'phone_number' => $customer->phone_number,
            'profile_image' => $customer->profile_image,
        ]);
    }

    public function deleteProfile(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'Unauthorized.'], 200);
        }

        $customer = $user->customer;

        $hasOrders = $customer->purchaseOrders()->exists();

        if ($hasOrders) {
            return response()->json(['message' => 'Cannot delete account. Active purchase orders found.'], 200);
        }

        if ($customer->profile_image && Storage::disk('public')->exists($customer->profile_image)) {
            Storage::disk('public')->delete($customer->profile_image);
        }

        $customer->delete();

        $customer->user->delete();

        return response()->json(['message' => 'Your profile has been deleted successfully.'], 200);
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



    public function addFeedback(Request $request)
    {
        $user = auth()->user();
        $customer = $user->customer;

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 200);
        }

        $request->validate([
            'rate' => 'required|numeric|min:1|max:5',
            'feedback' => 'required|string|max:500',
            'item_id' => 'nullable|exists:items,id',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        $item_id = $request->input('item_id');
        $room_id = $request->input('room_id');

        $purchaseOrders = PurchaseOrder::where('customer_id', $customer->id)->get();

        if ($room_id) {
            $roomOrderExists = RoomOrder::where('room_id', $room_id)
                ->whereIn('purchase_order_id', $purchaseOrders->pluck('id'))
                ->exists();

            if (!$roomOrderExists) {
                return response()->json(['message' => 'The room is not part of your purchase history'], 200);
            }

            $existingRating = Rating::where('customer_id', $customer->id)
                ->where('room_id', $room_id)
                ->first();

            if ($existingRating) {
                return response()->json(['message' => 'You have already provided feedback for this room'], 200);
            }

            $rating = new Rating();
            $rating->customer_id = $customer->id;
            $rating->room_id = $room_id;
            $rating->rate = (float)$request->rate;
            $rating->feedback = $request->feedback;
            $rating->save();

            return response()->json(['message' => 'Feedback added successfully for room'], 201);
        }

        if ($item_id) {
            $itemOrderExists = ItemOrder::where('item_id', $item_id)
                ->whereIn('purchase_order_id', $purchaseOrders->pluck('id'))
                ->exists();

            if (!$itemOrderExists) {
                return response()->json(['message' => 'The item is not part of your purchase history'], 200);
            }


            $existingRating = Rating::where('customer_id', $customer->id)
                ->where('item_id', $item_id)
                ->first();

            if ($existingRating) {
                return response()->json(['message' => 'You have already provided feedback for this item'], 200);
            }

            $rating = new Rating();
            $rating->customer_id = $customer->id;
            $rating->item_id = $item_id;
            $rating->rate = (float)$request->rate;
            $rating->feedback = $request->feedback;
            $rating->save();

            return response()->json(['message' => 'Feedback added successfully for item'], 201);
        }

        return response()->json(['message' => 'You must provide either item_id or room_id'], 200);
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

    public function getUserSpecificFeedback(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'User not found or not logged in'], 200);
        }

        $request->validate([
            'item_id' => 'nullable|exists:items,id',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        if (empty($request->item_id) && empty($request->room_id)) {
            return response()->json(['message' => 'You must provide either item_id or room_id'], 200);
        }

        $customerId = $user->customer->id;

        if ($request->room_id) {
            $feedback = Rating::where('customer_id', $customerId)
                ->where('room_id', $request->room_id)
                ->first();

            if (!$feedback) {
                return response()->json(['message' => 'No feedback found for this room from the user'], 200);
            }

            return response()->json([
                'room_id' => $feedback->room_id,
                'rate' => $feedback->rate,
                'feedback' => $feedback->feedback,
                'submitted_at' => $feedback->created_at->toDateTimeString(),
            ], 200);
        }

        if ($request->item_id) {
            $feedback = Rating::where('customer_id', $customerId)
                ->where('item_id', $request->item_id)
                ->first();

            if (!$feedback) {
                return response()->json(['message' => 'No feedback found for this item from the user'], 200);
            }

            return response()->json([
                'item_id' => $feedback->item_id,
                'rate' => $feedback->rate,
                'feedback' => $feedback->feedback,
                'submitted_at' => $feedback->created_at->toDateTimeString(),
            ], 200);
        }
    }

    public function getFeedbackAndRatings(Request $request)
    {
        $itemId = $request->input('item_id');
        $roomId = $request->input('room_id');

        if (empty($itemId) && empty($roomId)) {
            return response()->json(['message' => 'You must provide either item_id or room_id'], 200);
        }

        $request->validate([
            'item_id' => 'nullable|exists:items,id',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        $query = Rating::with(['customer.user', 'item', 'room']);

        if (!empty($itemId)) {
            $query->where('item_id', $itemId);
        } elseif (!empty($roomId)) {
            $query->where('room_id', $roomId);
        }

        $feedbacks = $query->get();
        $averageRate = $feedbacks->avg('rate');

        $data = $feedbacks->map(function ($feedback) use ($itemId) {
            $itemOrRoom = $itemId ? $feedback->item : $feedback->room;

            return [
                'rate' => $feedback->rate,
                'feedback' => $feedback->feedback,
                'customer_id' => $feedback->customer->id,
                'customer_name' => $feedback->customer->user->name ?? 'Unknown',
                'customer_email' => $feedback->customer->user->email ?? 'Unknown',
                'customer_image' => $feedback->customer->profile_image ?? null,
                'item_or_room_image' => $itemOrRoom->image_url ?? null,
                'submitted_at' => $feedback->created_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'message' => 'Feedback and ratings retrieved successfully',
            'average_rate' => round($averageRate, 2),
            'data' => $data,
        ], 200);
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

    public function getRoomCustomization($roomId)
    {
        $user = auth()->user();
        if (!$user || !$user->customer) {
            return response()->json(['message' => 'login is required!'], 200);
        }

        $customerId = $user->customer->id;

        $roomCustomization = RoomCustomization::where('room_id', $roomId)
            ->where('customer_id', $customerId)
            ->with('customizedItems.item', 'customizedItems.customization')
            ->first();

        $room = Room::with('items')->find($roomId);

        if (!$room) {
            return response()->json(['message' => 'Room not found'], 200);
        }

        $items = $room->items->map(function ($item) use ($roomCustomization) {
            $customizedItem = optional($roomCustomization)->customizedItems
                ->where('item_id', $item->id)
                ->first();

            return [
                'item' => $item,
                'is_customized' => $customizedItem ? true : false,
                'customization' => $customizedItem ? $customizedItem->customization : null,
            ];
        });

        return response()->json([
            'room_id' => $room->id,
            'room_name' => $room->name,
            'items' => $items,
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


    public function addToCart2(Request $request)
    {
        $user = auth()->user();
        $customerId = $user->customer->id;
        $this->validateCartReservations($customerId);

        $requiredFields = ['item_id', 'room_id', 'customization_id', 'room_customization_id'];
        $isValid = false;

        foreach ($requiredFields as $field) {
            if ($request->has($field)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            return response()->json(['message' => 'Invalid request. Missing one of item_id, room_id, customization_id, or room_customization_id'], 200);
        }

        $count = (int) $request->input('count', 1);
        if ($count <= 0) {
            return response()->json(['message' => 'Count must be greater than 0'], 200);
        }

        $itemId = $request->input('item_id');
        $roomId = $request->input('room_id');
        $customizationId = $request->input('customization_id');
        $roomCustomizationId = $request->input('room_customization_id');

        $pricePerItem = 0.00;
        $timePerItem = 0.00;
        $availableCount = 0;
        $partialTime = 0;

        $cartQuery = Cart::where('customer_id', $customerId);

        if ($itemId) {
            $item = Item::find($itemId);
            if (!$item) return response()->json(['message' => 'Item not found'], 200);
            $availableCount = $item->count - $item->count_reserved;
            $pricePerItem = (float) $item->price;
            $timePerItem = (float) $item->time;

            $cartQuery->where('item_id', $itemId)->whereNull('room_id')->whereNull('customization_id')->whereNull('room_customization_id');
            $cart = $cartQuery->first();

            if ($cart) {
                $oldCount = $cart->count;
                $newCount = $oldCount + $count;

                $availableNow = $item->count - $item->count_reserved;
                $missingAdded = max(0, $count - $availableNow);

                if ($availableNow > 0) {
                    $item->count_reserved += min($count, $availableNow);
                    $item->save();
                }

                $extraTime = $missingAdded * $timePerItem;

                $cart->count = $newCount;
                $cart->price = $pricePerItem * $newCount;
                $cart->time += $extraTime;
                $cart->reserved_at = now();
                $cart->save();

                $partialTime = $extraTime;
            } else {
                $missingCount = max(0, $count - $availableCount);
                $partialTime = $missingCount * $timePerItem;

                if ($availableCount > 0) {
                    $item->count_reserved += min($count, $availableCount);
                    $item->save();
                }

                $cart = Cart::create([
                    'customer_id' => $customerId,
                    'item_id' => $itemId,
                    'count' => $count,
                    'time_per_item' => $timePerItem,
                    'price_per_item' => $pricePerItem,
                    'time' => $partialTime,
                    'price' => $pricePerItem * $count,
                    'available_count_at_addition' => $availableCount,
                    'reserved_at' => now(),
                ]);
            }
        } elseif ($roomId) {
            $room = Room::find($roomId);
            if (!$room) {
                return response()->json(['message' => 'Room not found'], 200);
            }

            $roomPricePerItem = (float) $room->price; // السعر من جدول rooms
            $roomTimePerItem = (float) $room->time;   // الوقت من جدول rooms

            $cartQuery->where('room_id', $roomId)
                ->whereNull('item_id')
                ->whereNull('customization_id')
                ->whereNull('room_customization_id');

            $cart = $cartQuery->first();

            if ($cart) {
                $cart->count += $count;
                $cart->price = $roomPricePerItem * $cart->count;
                $cart->time = $roomTimePerItem * $cart->count;
                $cart->time_per_item = $roomTimePerItem;
                $cart->price_per_item = $roomPricePerItem;
                $cart->reserved_at = now();
                $cart->save();
            } else {
                $cart = Cart::create([
                    'customer_id' => $customerId,
                    'room_id' => $roomId,
                    'count' => $count,
                    'time_per_item' => $roomTimePerItem,
                    'price_per_item' => $roomPricePerItem,
                    'time' => $roomTimePerItem * $count,
                    'price' => $roomPricePerItem * $count,
                    'reserved_at' => now(),
                ]);
            }

            $pricePerItem = $roomPricePerItem;
            $timePerItem = $roomTimePerItem;
        }


        $cartItems = Cart::where('customer_id', $customerId)->get();
        $totalCartPrice = $cartItems->sum('price');
        $totalCartTime = $cartItems->sum('time');
        $depositAmount = $totalCartPrice * 0.5;

        return response()->json([
            'message' => 'Added/Updated successfully in cart',
            'cart' => $cart,
            'total_time' => $totalCartTime,
            'total_price' => $totalCartPrice,
            'deposit' => $depositAmount,
            'item_time' => $timePerItem,
            'item_price' => $pricePerItem * $count,
        ]);
    }



    public function getNearestBranch(Request $request)
    {
        $userLat = $request->input('latitude');
        $userLng = $request->input('longitude');

        if (!$userLat || !$userLng) {
            return response()->json(['message' => 'Latitude and longitude are required.'], 422);
        }

        $nearestBranch = Branch::selectRaw("*, 
        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) 
        * cos(radians(longitude) - radians(?)) 
        + sin(radians(?)) * sin(radians(latitude)))) AS distance", [
            $userLat,
            $userLng,
            $userLat
        ])
            ->orderBy('distance')
            ->first();

        if (!$nearestBranch) {
            return response()->json(['message' => 'No branches found.'], 404);
        }

        return response()->json([
            'message' => 'Nearest branch retrieved successfully.',
            'branch' => [
                'id'          => $nearestBranch->id,
                'address'     => $nearestBranch->address,
                'latitude'    => $nearestBranch->latitude,
                'longitude'   => $nearestBranch->longitude,
                'distance_km' => round($nearestBranch->distance, 2),
            ]
        ]);
    }

    public function getDeliveryPrice(Request $request)
{
    $request->validate([
        'address'   => 'required|string',
        'latitude'  => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
    ]);

    $deliveryPrice = 0;

    $placeCost = PlaceCost::where('place', $request->input('address'))->first();

    if (!$placeCost) {
        return response()->json([
            'message' => 'Delivery price not found for the given address.',
            'delivery_price' => null
        ], 404);
    }

    $deliveryPrice = $placeCost->price;

    $customerId = auth()->user()->customer->id;
    $cartItems = \App\Models\Cart::where('customer_id', $customerId)->get();

    $totalCartPrice = $cartItems->sum('price');  

    $totalWithDelivery = $totalCartPrice + $deliveryPrice;

    return response()->json([
        'message' => 'Delivery price and total price with delivery retrieved successfully.',
        'delivery_price' => round($deliveryPrice, 2),
        'total_price_with_delivery' => round($totalWithDelivery, 2)
    ]);
}



    public function confirmCart(Request $request)
    {
        $user = auth()->user();
        $customerId = $user->customer->id;

        $cartItems = Cart::where('customer_id', $customerId)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty'], 200);
        }

        $wantDelivery = $request->input('want_delivery');
        if (!in_array($wantDelivery, ['yes', 'no'])) {
            return response()->json(['message' => 'The field want_delivery is required and must be yes or no']);
        }

        if ($wantDelivery === 'yes') {
            if (!$request->has(['latitude', 'longitude'])) {
                return response()->json(['message' => 'Latitude and longitude are required when delivery is wanted.']);
            }

            if (!$request->has('address') || empty($request->input('address'))) {
                return response()->json(['message' => 'Address is required when delivery is wanted.']);
            }
        }

        $totalPrice = 0;
        $totalTime = 0;

        foreach ($cartItems as $cartItem) {
            $totalPrice += $cartItem->price;
            $totalTime += $cartItem->time;
        }

        $rabbon = $totalPrice * 0.5;
        $priceAfterRabbon = $totalPrice - $rabbon;

        $wallet = $user->wallets->first();

        if (!$wallet || $wallet->balance < $rabbon) {
            return response()->json(['message' => 'Insufficient balance to pay the deposit (rabbon)'], 200);
        }

        $wallet->balance -= $rabbon;
        $wallet->save();

        $manager = GallaryManager::with('user')->first();
        $user = $manager?->user;
        if (!$manager || !$manager->user->wallets) {
            return response()->json(['message' => 'Manager wallet not found'], 500);
        }
        $managerWallet = $manager->user->wallets->first();
        $managerWallet->balance += $rabbon;
        $managerWallet->save();

        $deliveryPrice = 0;
        $nearestBranch = null;

        if ($wantDelivery === 'yes') {
            $deliveryRequest = new \Illuminate\Http\Request([
                'address' => $request->input('address'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ]);

            $deliveryResponse = $this->getDeliveryPrice($deliveryRequest);
            $responseData = $deliveryResponse->getData(true);

            $responseData = $deliveryResponse->getData(true);

            if ($deliveryResponse->getStatusCode() === 200) {
                $deliveryPrice = $responseData['delivery_price'];
            } else {
                return response()->json(['message' => $responseData['message']]);
            }
        } else {
            $branchRequest = new \Illuminate\Http\Request([
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ]);

            $branchResponse = $this->getNearestBranch($branchRequest);

            $responseData = $branchResponse->getData(true);

            if ($branchResponse->getStatusCode() === 200) {
                $nearestBranch = $responseData['branch'];
            } else {
                return response()->json(['message' => $responseData['message']]);
            }
        }

        $priceAfterRabbonWithDelivery = $priceAfterRabbon + $deliveryPrice;
        $remainingAmount = $priceAfterRabbon;
        $remainingAmountWithDelivery = $wantDelivery === 'yes' ? $priceAfterRabbonWithDelivery : null;

        $purchaseOrder = PurchaseOrder::create([
            'customer_id'                       => $customerId,
            'total_price'                       => $totalPrice,
            'status'                            => 'not_ready',
            'is_paid'                           => 'pending',
            'is_recived'                        => 'pending',
            'want_delivery'                    => $wantDelivery,
            'recive_date'                       => $request->input('recive_date', now()),
            'latitude'                          => $request->input('latitude'),
            'longitude'                         => $request->input('longitude'),
            'address'                           => $request->input('address'),
            'delivery_price'                    => $deliveryPrice,
            'rabbon'                            => $rabbon,
            'price_after_rabbon'               => $priceAfterRabbon,
            'price_after_rabbon_with_delivery' => $wantDelivery === 'yes' ? $priceAfterRabbonWithDelivery : null,
            'remaining_amount'                 => $remainingAmount,
            'remaining_amount_with_delivery'   => $remainingAmountWithDelivery,
            'branch_id'                        => $wantDelivery === 'no' && $nearestBranch ? $nearestBranch['id'] : null,
        ]);

        foreach ($cartItems as $cartItem) {
            $countRequested = $cartItem->count;

            if ($cartItem->item_id) {
                $item = Item::find($cartItem->item_id);
                if ($item) {
                    $available = $item->count - $item->count_reserved;
                    $shortage = max(0, $countRequested - $available);

                    if ($shortage > 0) {
                        WorkshopManagerRequest::create([
                            'item_id'           => $item->id,
                            'purchase_order_id' => $purchaseOrder->id,
                            'required_count'    => $shortage,
                            'status'            => 'pending',
                            'notes'             => 'Auto-generated due to item shortage',
                        ]);
                    }

                    $purchaseOrder->item()->attach($item->id, [
                        'count'          => $countRequested,
                        'deposite_price' => $cartItem->price_per_item,
                        'deposite_time'  => $cartItem->time_per_item,
                        'delivery_time'  => $totalTime,
                    ]);
                }
            }

            if ($cartItem->room_id) {
                $purchaseOrder->roomOrders()->create([
                    'room_id'           => $cartItem->room_id,
                    'count'             => $countRequested,
                    'deposite_price'    => $cartItem->price_per_item,
                    'deposite_time'     => $cartItem->time_per_item,
                    'purchase_order_id' => $purchaseOrder->id,
                ]);

                $roomItems = Item::where('room_id', $cartItem->room_id)->get();
                foreach ($roomItems as $roomItem) {
                    $available = $roomItem->count - $roomItem->count_reserved;
                    $shortage = max(0, $countRequested - $available);

                    if ($shortage > 0) {
                        WorkshopManagerRequest::create([
                            'item_id'           => $roomItem->id,
                            'purchase_order_id' => $purchaseOrder->id,
                            'required_count'    => $shortage,
                            'status'            => 'pending',
                            'notes'             => 'Auto-generated from room shortage',
                        ]);
                    }
                }
            }

            // if ($cartItem->customization_id) {
            //     $purchaseOrder->customizationOrders()->create([
            //         'customization_id' => $cartItem->customization_id,
            //         'count'            => $countRequested,
            //         'deposite_price'   => $cartItem->price_per_item,
            //         'deposite_time'    => $cartItem->time_per_item,
            //     ]);
            // }

            // if ($cartItem->room_customization_id) {
            // $purchaseOrder->roomCustomizationOrders()->create([
            //     'room_customization_id' => $cartItem->room_customization_id,
            //     'count'                 => $countRequested,
            //     'deposite_price'        => $cartItem->price_per_item,
            //     'deposite_time'         => $cartItem->time_per_item,
            // ]);
            // }

            $cartItem->delete();
        }

        // if ($request->has('available_times') && is_array($request->available_times)) {
        //     foreach ($request->available_times as $availableTime) {
        //         CustomerAvailableTime::create([
        //             'customer_id'       => $customerId,
        //             'purchase_order_id' => $purchaseOrder->id,
        //             'available_at'      => $availableTime,
        //         ]);
        //     }
        // }

        return response()->json([
            'message' => 'Your order has been confirmed successfully!',
            'order'   => $purchaseOrder,
            'price_details' => [
                'total_price'                     => $totalPrice,
                'rabbon'                          => $rabbon,
                'price_after_rabbon'              => $priceAfterRabbon,
                'delivery_price'                  => $deliveryPrice,
                'price_after_rabbon_with_delivery' => $priceAfterRabbonWithDelivery,
                'remaining_amount'                => $remainingAmount,
                'remaining_amount_with_delivery'  => $remainingAmountWithDelivery,
            ],
            'nearest_branch' => $nearestBranch,
        ]);
    }

    public function ChargeInvestmentWallet(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            'payment_method' => 'required|string',
            'currency' => 'nullable|string',
        ]);

        try {
            $user = auth()->user();
            $wallet = $user->wallets()->where('wallet_type', 'investment')->first();

            if (!$wallet) {
                return response()->json(['message' => 'Wallet not found'], 404);
            }

            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

            $charge = $stripe->charges->create([
                'amount' => $request->amount,
                'currency' => $request->currency ?? 'usd',
                'source' => $request->token,
                'description' => $request->description ?? 'Wallet top-up',
            ]);

            $amountInDollars = $request->amount;

            DB::transaction(function () use ($user, $wallet, $charge, $amountInDollars, $request) {
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $amountInDollars,
                    'type' => 'deposit',
                    'status' => 'completed',
                    'stripe_payment_id' => $charge->id,
                ]);

                StripePayment::create([
                    'transaction_id' => $transaction->id,
                    'payment_intent_id' => $charge->id,
                    'amount' => $amountInDollars,
                    'currency' => $charge->currency,
                    'payment_method' => $charge->payment_method ?? $request->payment_method,
                    'status' => $charge->status,
                    'receipt_url' => $charge->receipt_url ?? null,
                ]);

                $wallet->increment('balance', $amountInDollars);
            });

            return response()->json(['message' => 'Wallet charged successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 500);
        }
    }




    function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371)
    {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }





    //Don't Test
    public function removePartialFromCart(Request $request)
    {
        $request->validate([
            'item_id' => 'nullable|integer|exists:items,id',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'customization_id' => 'nullable|integer|exists:customizations,id',
            'room_customization_id' => 'nullable|integer|exists:room_customizations,id',
            'count' => 'required|integer|min:1',
        ]);

        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'login is required'], 200);
        }

        $cartQuery = Cart::where('customer_id', $user->customer->id);

        if ($request->filled('item_id')) {
            $cartQuery->where('item_id', $request->item_id)
                ->whereNull('room_id')
                ->whereNull('customization_id')
                ->whereNull('room_customization_id');
        } elseif ($request->filled('room_id')) {
            $cartQuery->where('room_id', $request->room_id)
                ->whereNull('item_id')
                ->whereNull('customization_id')
                ->whereNull('room_customization_id');
        } elseif ($request->filled('customization_id')) {
            $cartQuery->where('customization_id', $request->customization_id)
                ->whereNull('item_id')
                ->whereNull('room_id')
                ->whereNull('room_customization_id');
        } elseif ($request->filled('room_customization_id')) {
            $cartQuery->where('room_customization_id', $request->room_customization_id)
                ->whereNull('item_id')
                ->whereNull('room_id')
                ->whereNull('customization_id');
        } else {
            return response()->json(['message' => 'You must provide one of item_id, room_id, customization_id, or room_customization_id'], 200);
        }

        $cartItem = $cartQuery->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Item not found in cart'], 200);
        }

        if ($cartItem->count < $request->count) {
            return response()->json(['message' => 'Count you sent is bigger than count in cart'], 200);
        }

        $removalCount = $request->count;

        if ($cartItem->item_id) {
            $item = Item::find($cartItem->item_id);
            if ($item) {
                $item->count_reserved = max(0, $item->count_reserved - $removalCount);
                $item->save();
            }
        } elseif ($cartItem->room_id) {
            $room = Room::with('items')->find($cartItem->room_id);
            if ($room) {
                foreach ($room->items as $roomItem) {
                    $roomItem->count_reserved = max(0, $roomItem->count_reserved - $removalCount);
                    $roomItem->save();
                }
            }
        }

        if ($cartItem->count > $removalCount) {
            $unitPrice = $cartItem->price_per_item;
            $unitTime = $cartItem->time_per_item;

            $cartItem->count -= $removalCount;
            $cartItem->price = $unitPrice * $cartItem->count;
            $cartItem->time = $unitTime * $cartItem->count;
            $cartItem->save();
        } else {
            $cartItem->delete();
        }

        $cartItems = Cart::where('customer_id', $user->customer->id)->get();

        $totalPrice = $cartItems->sum('price');
        $totalTime = $cartItems->max('time');

        return response()->json([
            'message' => 'Item removed or updated in cart',
            'current_cart' => $cartItems,
            'total_price' => $totalPrice,
            'total_time' => $totalTime,
        ], 200);
    }

    public function deleteCart()
    {
        $user = auth()->user();
        $customerId = $user->customer->id;

        $cartItems = Cart::where('customer_id', $customerId)->get();

        foreach ($cartItems as $cartItem) {
            if ($cartItem->item_id) {
                $item = Item::find($cartItem->item_id);
                if ($item) {
                    $item->count_reserved = max(0, $item->count_reserved - $cartItem->count);
                    $item->save();
                }
            } elseif ($cartItem->room_id) {
                $room = Room::with('items')->find($cartItem->room_id);
                if ($room) {
                    foreach ($room->items as $roomItem) {
                        $roomItem->count_reserved = max(0, $roomItem->count_reserved - $cartItem->count);
                        $roomItem->save();
                    }
                }
            }
        }

        Cart::where('customer_id', $customerId)->delete();

        return response()->json(['message' => 'Cart and reservations cleared']);
    }

    private function validateCartReservations($customerId)
    {
        $cartItems = Cart::where('customer_id', $customerId)->get();
        foreach ($cartItems as $cartItem) {
            $elapsed = now()->diffInHours($cartItem->reserved_at);

            if ($elapsed >= 24) {
                if ($cartItem->item_id) {
                    $item = Item::find($cartItem->item_id);
                    if (!$item) continue;

                    $available = $item->count - $item->count_reserved;
                    $needed = $cartItem->count;

                    if ($available >= $needed) {
                        $cartItem->reserved_at = now();
                        $cartItem->time = 0;
                    } elseif ($available > 0) {
                        $missing = $needed - $available;
                        $cartItem->reserved_at = now();
                        $cartItem->time = $missing * $item->time;
                    } else {
                        $cartItem->time = $needed * $item->time;
                    }

                    $cartItem->save();
                } elseif ($cartItem->room_id) {
                    $room = Room::with('items')->find($cartItem->room_id);
                    if (!$room) continue;

                    $totalMissingTime = 0;
                    foreach ($room->items as $roomItem) {
                        $available = $roomItem->count - $roomItem->count_reserved;
                        $needed = $cartItem->count;

                        if ($available >= $needed) {
                            continue;
                        } elseif ($available > 0) {
                            $missing = $needed - $available;
                            $totalMissingTime += $missing * $roomItem->time;
                        } else {
                            $totalMissingTime += $needed * $roomItem->time;
                        }
                    }

                    $cartItem->reserved_at = now();
                    $cartItem->time = $totalMissingTime;
                    $cartItem->save();
                }
            }
        }
    }

    public function getType()
    {
        $itemTypes = ItemType::all();
        return response()->json($itemTypes);
    }

    public function getItemsByType($typeId)
    {
        $type = ItemType::find($typeId);

        if (!$type) {
            return response()->json([
                'message' => 'Type not found',
            ], 200);
        }

        $items = Item::where('item_type_id', $typeId)->get();

        return response()->json([
            'type' => $type->name,
            'items' => $items,
        ]);
    }

    public function searchItemsByTypeName(Request $request)
    {
        $typeName = $request->query('type_name');

        if (!$typeName || !is_string($typeName)) {
            return response()->json([
                'message' => 'type_name is required and must be a string'
            ], 200);
        }

        $types = ItemType::where('name', 'like', '%' . $typeName . '%')->get();

        if ($types->isEmpty()) {
            return response()->json([
                'message' => 'Type not found'
            ], 200);
        }

        $items = Item::whereIn('item_type_id', $types->pluck('id'))
            ->with(['itemType', 'likes', 'ratings'])
            ->get();

        $itemsWithTypeName = $items->map(function ($item) {
            $likesCount = $item->likes->count();
            $averageRating = (float) $item->ratings->avg('rate');

            return [
                'id' => $item->id,
                'room_id' => $item->room_id,
                'name' => $item->name,
                'time' => $item->time,
                'price' => $item->price,
                'image_url' => $item->image_url,
                'count' => $item->count,
                'count_reserved' => $item->count_reserved,
                'item_type_id' => $item->item_type_id,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'type' => $item->itemType->name,
                'likes_count' => $likesCount,
                'total_rating' => (float) $averageRating ?? 0.0,
            ];
        });

        return response()->json([
            'types' => $types->pluck('name'),
            'items' => $itemsWithTypeName
        ]);
    }


    public function filterItemsWithType(Request $request)
    {
        $request->validate([
            'type_name' => 'required|string',
            'fabric_color' => 'nullable|string',
            'fabric_name' => 'nullable|string',
            'wood_name' => 'nullable|string',
            'price_min' => 'nullable|numeric',
            'price_max' => 'nullable|numeric',
        ]);

        $types = ItemType::where('name', 'like', '%' . $request->type_name . '%')->get();

        if ($types->isEmpty()) {
            return response()->json(['message' => 'Type not found'], 200);
        }

        $query = Item::select('items.*')
            ->join('item_details', 'item_details.item_id', '=', 'items.id')
            ->join('fabrics', 'fabrics.id', '=', 'item_details.fabric_id')
            ->join('woods', 'woods.id', '=', 'item_details.wood_id')
            ->whereIn('item_type_id', $types->pluck('id'));

        if ($request->filled('price_min') && $request->filled('price_max')) {
            $query->whereBetween('items.price', [$request->price_min, $request->price_max]);
        } elseif ($request->filled('price_min')) {
            $query->where('items.price', '>=', $request->price_min);
        } elseif ($request->filled('price_max')) {
            $query->where('items.price', '<=', $request->price_max);
        }

        if ($request->fabric_color) {
            $query->where('item_details.fabric_color', 'like', '%' . $request->fabric_color . '%');
        }

        if ($request->fabric_name) {
            $query->where('fabrics.name', 'like', '%' . $request->fabric_name . '%');
        }

        if ($request->wood_name) {
            $query->where('woods.name', 'like', '%' . $request->wood_name . '%');
        }

        $items = $query->get();

        $itemsFormatted = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'room_id' => $item->room_id,
                'name' => $item->name,
                'price' => $item->price,
                'type' => $item->itemType->name ?? null,
                'fabric_color' => $item->itemDetail->fabric_color ?? null,
                'fabric_name' => $item->itemDetail->fabric->name ?? null,
                'wood_name' => $item->itemDetail->wood->name ?? null,
            ];
        });

        return response()->json([
            'types' => $types->pluck('name'),
            'items' => $itemsFormatted
        ]);
    }


    public function addToCartFavorite(Request $request)
    {
        $user = auth()->user();
        $customerId = $user->customer->id;
        $this->validateCartReservations($customerId);

        $itemIds = $request->input('item_ids', []);
        $roomIds = $request->input('room_ids', []);
        $singleItemId = $request->input('item_id');
        $singleRoomId = $request->input('room_id');

        if (empty($itemIds) && empty($roomIds) && !$singleItemId && !$singleRoomId) {
            return response()->json(['message' => 'Invalid request. Provide item_ids or room_ids or single item_id/room_id'], 200);
        }

        if ($singleItemId) $itemIds[] = $singleItemId;
        if ($singleRoomId) $roomIds[] = $singleRoomId;

        $cartResponses = [];

        foreach ($itemIds as $itemId) {
            $count = 1;
            $item = Item::find($itemId);

            if (!$item) {
                $cartResponses[] = ['item_id' => $itemId, 'error' => 'Item not found'];
                continue;
            }

            $availableCount = $item->count - $item->count_reserved;
            $pricePerItem = (float) $item->price;
            $timePerItem = (float) $item->time;
            $partialTime = 0;

            $cart = Cart::where('customer_id', $customerId)
                ->where('item_id', $itemId)
                ->whereNull('room_id')->whereNull('customization_id')->whereNull('room_customization_id')
                ->first();

            if ($cart) {
                $oldCount = $cart->count;
                $newCount = $oldCount + $count;
                $missingAdded = max(0, $count - $availableCount);

                if ($availableCount > 0) {
                    $item->count_reserved += min($count, $availableCount);
                    $item->save();
                }

                $extraTime = $missingAdded * $timePerItem;

                $cart->count = $newCount;
                $cart->price = $pricePerItem * $newCount;
                $cart->time += $extraTime;
                $cart->reserved_at = now();
                $cart->save();

                $partialTime = $extraTime;
            } else {
                $missingCount = max(0, $count - $availableCount);
                $partialTime = $missingCount * $timePerItem;

                if ($availableCount > 0) {
                    $item->count_reserved += min($count, $availableCount);
                    $item->save();
                }

                $cart = Cart::create([
                    'customer_id' => $customerId,
                    'item_id' => $itemId,
                    'count' => $count,
                    'time_per_item' => $timePerItem,
                    'price_per_item' => $pricePerItem,
                    'time' => $partialTime,
                    'price' => $pricePerItem * $count,
                    'available_count_at_addition' => $availableCount,
                    'reserved_at' => now(),
                ]);
            }

            $cartResponses[] = [
                'type' => 'item',
                'item_id' => $itemId,
                'cart' => $cart,
                'item_price' => $pricePerItem * $count,
                'item_time' => $partialTime
            ];
        }

        foreach ($roomIds as $roomId) {
            $count = 1; // دائمًا 1
            $room = Room::with('items')->find($roomId);

            if (!$room) {
                $cartResponses[] = ['room_id' => $roomId, 'error' => 'Room not found'];
                continue;
            }

            $roomPricePerItem = 0.0;
            $roomTimePerItem = 0.0;
            $partialTime = 0.0;

            foreach ($room->items as $roomItem) {
                $roomPricePerItem += $roomItem->price;
                $roomTimePerItem += $roomItem->time;

                $available = $roomItem->count - $roomItem->count_reserved;
                $missing = max(0, $count - $available);
                $partialTime += $missing * $roomItem->time;

                if ($available > 0) {
                    $roomItem->count_reserved += min($count, $available);
                    $roomItem->save();
                }
            }

            $cart = Cart::where('customer_id', $customerId)
                ->where('room_id', $roomId)
                ->whereNull('item_id')->whereNull('customization_id')->whereNull('room_customization_id')
                ->first();

            if ($cart) {
                $cart->count += $count;
                $cart->price = $roomPricePerItem * $cart->count;
                $cart->time += $partialTime;
                $cart->time_per_item = $roomTimePerItem;
                $cart->price_per_item = $roomPricePerItem;
                $cart->reserved_at = now();
                $cart->save();
            } else {
                $cart = Cart::create([
                    'customer_id' => $customerId,
                    'room_id' => $roomId,
                    'count' => $count,
                    'time_per_item' => $roomTimePerItem,
                    'price_per_item' => $roomPricePerItem,
                    'time' => $partialTime,
                    'price' => $roomPricePerItem * $count,
                    'reserved_at' => now(),
                ]);
            }

            $cartResponses[] = [
                'type' => 'room',
                'room_id' => $roomId,
                'cart' => $cart,
                'item_price' => $roomPricePerItem * $count,
                'item_time' => $partialTime
            ];
        }

        $cartItems = Cart::where('customer_id', $customerId)->get();
        $totalCartPrice = $cartItems->sum('price');
        $totalCartTime = $cartItems->sum('time');
        $depositAmount = $totalCartPrice * 0.5;

        return response()->json([
            'message' => 'Batch add/update to cart completed',
            // 'results' => $cartResponses,
            // 'total_time' => $totalCartTime,
            // 'total_price' => $totalCartPrice,
            // 'deposit' => $depositAmount,
        ]);
    }


    public function showDiscountDetails($id)
    {
        $discount = Discount::with(['room.items', 'item'])->findOrFail($id);

        $originalPrice = $discount->item
            ? $discount->item->price
            : ($discount->room ? $discount->room->price : 0);

        $discountedPrice = $originalPrice - ($originalPrice * ($discount->discount_percentage / 100));

        $response = [
            'id' => $discount->id,
            'original_price' => (float) number_format($originalPrice, 2, '.', ''),
            'discounted_price' => (float) number_format($discountedPrice, 2, '.', ''),
            'discount_percentage' => $discount->discount_percentage,
            'item_id' => $discount->item ? $discount->item->id : null,
            'item_img' => $discount->item ? $discount->item->image_url : null,
            'item_name' => $discount->item ? $discount->item->name : null,
            'room_id' => $discount->room ? $discount->room->id : null,
            'room_img' => $discount->room ? $discount->room->image_url : null,
            'room_name' => $discount->room ? $discount->room->name : null,
            'start_date' => \Carbon\Carbon::parse($discount->start_date)->format('Y-m-d'),
            'end_date' => \Carbon\Carbon::parse($discount->end_date)->format('Y-m-d'),
            'room_items' => $discount->room ? $discount->room->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => (float) number_format($item->price, 2, '.', ''),
                    'image_url' => $item->image_url,
                ];
            })->toArray() : [],
        ];

        return response()->json($response);
    }



    public function getOrdersByCustomer()
    {
        $customerId = auth()->user()->customer->id;

        $customer = Customer::with(['purchaseOrders.item', 'purchaseOrders.roomOrders'])->find($customerId);


        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        return response()->json([
            'customer' => $customer->name,
            'orders' => $customer->purchaseOrders,
        ]);
    }
}