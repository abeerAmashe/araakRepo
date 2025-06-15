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
use App\Models\RoomOrder;
use App\Models\StripePayment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Wallet;


class CustomerController extends Controller
{
    public function getAllCategories()
    {
        $categories = Category::select('id', 'name')->distinct()->get();

        return response()->json(['categories' => $categories], 200);
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
    public function getUserBalance()
    {
        $user = auth()->user();

        $wallet = $user->wallets->where('is_active', true)->first();

        if (!$wallet) {
            return response()->json([
                'message' => 'No active wallet found for this user.',
                'balance' => 0
            ]);
        }

        return response()->json([
            'message' => 'Wallet balance retrieved successfully.',
            'balance' => $wallet->balance,
            'currency' => $wallet->currency,
        ]);
    }
    public function getAllOrders()
    {
        $orders = PurchaseOrder::select('id', 'status', 'recive_date', 'remaining_amount_with_delivery')
            ->get()
            ->map(function ($order) {
                $remainingTime = '00:00';

                if ($order->recive_date) {
                    $now = Carbon::now()->startOfDay();
                    $reciveDate = Carbon::parse($order->recive_date)->startOfDay();

                    if ($reciveDate->greaterThan($now)) {
                        $diffInDays = $now->diffInDays($reciveDate);
                        $remainingTime = sprintf('%02d:00', $diffInDays * 24); // عدد الساعات المتبقية (أيام * 24)
                    }
                }

                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'remaining_time' => $remainingTime,
                    'remaining_bill' => number_format($order->remaining_amount_with_delivery, 2) . ' $',
                ];
            });

        return response()->json([
            'message' => 'Orders retrieved successfully.',
            'orders' => $orders,
        ]);
    }
    public function getOrderDetails($orderId)
    {
        $order = PurchaseOrder::with(['roomOrders.room', 'item'])->find($orderId);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found.',
            ], 404);
        }

        $remainingTime = '00:00';
        if ($order->recive_date) {
            $now = Carbon::now()->startOfDay();
            $reciveDate = Carbon::parse($order->recive_date)->startOfDay();

            if ($reciveDate->greaterThan($now)) {
                $diffInDays = $now->diffInDays($reciveDate);
                $remainingTime = sprintf('%02d:00', $diffInDays * 24);
            }
        }

        $rooms = $order->roomOrders->map(function ($roomOrder) {
            return [
                'room_id'    => $roomOrder->room->id ?? null,
                'room_name'  => $roomOrder->room->name ?? null,
                'room_img'   => $roomOrder->room->image ?? null,
                'room_price' => $roomOrder->room->price ?? null,
            ];
        });

        $items = $order->item->map(function ($item) {
            $depositAmount = $item->price * 0.5;

            return [
                'item_id'        => $item->id,
                'item_name'      => $item->name,
                'item_image'     => $item->image_url,
                'item_price'     => $item->price,
                'count_ordered'  => $item->pivot->count ?? 0,
                'deposite_price' => number_format($depositAmount, 2), // 50% من السعر
            ];
        });

        return response()->json([
            'message' => 'Order details retrieved successfully.',
            'id' => $order->id,
            'status' => $order->status,
            'remaining_time' => $remainingTime,
            'remaining_bill' => number_format($order->remaining_amount_with_delivery, 2) . ' $',
            'rooms_ordered' => $rooms,
            'items_ordered' => $items,
        ]);
    }
    public function cancelOrder($orderId)
    {
        $order = PurchaseOrder::find($orderId);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $order->status = 'cancelled';
        $order->save();

        return response()->json(['message' => 'Order cancelled successfully']);
    }
}
