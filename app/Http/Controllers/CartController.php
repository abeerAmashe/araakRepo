<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\GallaryManager;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Room;
use App\Models\WorkshopManagerRequest;
use Illuminate\Http\Request;

class CartController extends Controller
{
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
}
