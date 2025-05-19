<?php

namespace App\Helpers;

use App\Models\Cart;
use App\Models\Item;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log; // استخدام Log بدلاً من info()

class CartHelper
{
    public static function validateCartReservations()
    {
        // الحصول على السلات التي تم حجزها منذ أكثر من 24 ساعة
        $expiredCarts = Cart::where('reserved_at', '<', Carbon::now()->subHours(24))->get();
        
        // التعامل مع كل سلة منتهية
        foreach ($expiredCarts as $cart) {
            // إذا كانت السلة تحتوي على عنصر (Item)
            if ($cart->item_id) {
                $item = Item::find($cart->item_id);
                if ($item) {
                    // التحقق من الكمية المتاحة في المخزون
                    $availableStock = $item->count - $item->count_reserved;

                    // إذا كانت الكمية المتاحة في المخزون تسمح بإرجاع الكمية المحجوزة
                    if ($availableStock >= $cart->count) {
                        // إعادة الحجز بناءً على المخزون المتاح
                        $item->count_reserved += $cart->count;
                        $item->save();
                    } else {
                        // إلغاء الحجز إذا كانت الكمية غير كافية
                        $item->count_reserved = max(0, $item->count_reserved - $cart->count);
                        $item->save();
                        // إلغاء الحجز بالكامل إذا لم يكن هناك مخزون كافي
                        Log::info("Insufficient stock to re-reserve Item ID: {$cart->item_id}. Reservation cancelled.");
                    }
                }
            }
            // إذا كانت السلة تحتوي على غرفة (Room)
            elseif ($cart->room_id) {
                $room = Room::with('items')->find($cart->room_id);
                if ($room) {
                    // التحقق من الكمية المتاحة في المخزون للغرفة
                    foreach ($room->items as $roomItem) {
                        $availableStock = $roomItem->count - $roomItem->count_reserved;

                        // إذا كانت الكمية المتاحة في المخزون تسمح بإرجاع الكمية المحجوزة
                        if ($availableStock >= $cart->count) {
                            // إعادة الحجز بناءً على المخزون المتاح
                            $roomItem->count_reserved += $cart->count;
                            $roomItem->save();
                        } else {
                            // إلغاء الحجز إذا كانت الكمية غير كافية
                            $roomItem->count_reserved = max(0, $roomItem->count_reserved - $cart->count);
                            $roomItem->save();
                            // إلغاء الحجز بالكامل إذا لم يكن هناك مخزون كافي
                            Log::info("Insufficient stock to re-reserve Room Item ID: {$roomItem->id}. Reservation cancelled.");
                        }
                    }
                }
            }
        }

        // تسجيل رسالة عند انتهاء العملية
        Log::info('Expired reservations checked and updated!');
    }
}