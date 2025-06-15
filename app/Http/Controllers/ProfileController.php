<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class ProfileController extends Controller
{
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
}
