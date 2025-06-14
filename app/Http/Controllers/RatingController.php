<?php

namespace App\Http\Controllers;

use App\Models\ItemOrder;
use App\Models\PurchaseOrder;
use App\Models\Rating;
use App\Models\RoomOrder;
use Illuminate\Http\Request;

class RatingController extends Controller
{
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
}