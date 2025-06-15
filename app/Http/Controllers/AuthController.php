<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;




class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'C_password' => 'required|same:password',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'phone_number' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $input = $request->only(['name', 'email', 'password']);
        $input['password'] = Hash::make($input['password']);
        $user = User::create($input);
        $user->wallets()->create([
            'balance' => 0.00,
            'currency' => 'usd',
            'wallet_type' => 'investment',
            'is_active' => true,
        ]);

        if ($request->hasFile('profile_image')) {
            $imageName = time() . '.' . $request->file('profile_image')->getClientOriginalExtension();

            $destinationPath = public_path('profile');
            $request->file('profile_image')->move($destinationPath, $imageName);

            $profileImageUrl = URL::to('profile/' . $imageName);
        } else {
            $profileImageUrl = URL::to('profile2/profile.jpeg');
        }

        Customer::create([
            'user_id' => $user->id,
            'address' => '',
            'profile_image' => $profileImageUrl,
            'phone_number' => $request->phone_number,
        ]);

        $token = $user->createToken('Personal Token')->plainTextToken;

        return response()->json([
            'message' => 'Done ^_^',
            'token' => $token,
        ], 200);
    }
    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!Hash::check($request->password, $user->password)) {
            return 'cannot login';
        }
        $token = $user->createToken('Personal Token');
        return response()->json(['token' => $token->plainTextToken, 'user' => $user]);
    }


    //logout
}