<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon; 
use Illuminate\Support\Facades\Auth;



class UserAuthController extends Controller
{

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string|unique:users',
            'fingerprint' => 'nullable|string|regex:/^[a-zA-Z0-9+\/=]+$/',
            'password' => 'nullable|required|unique:users',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $username = $request->first_name . ' ' . $request->last_name;
        $user->username = $username;
        $user->password = Hash::make($request->password); // تأكد من أنك تقوم بتشفير كلمة السر
        $user->fingerprint = $request->fingerprint;
        $user->is_blocked = "0";


        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $path = $avatar->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        // إنشاء التوكن مع مدة صلاحية 4 أيام
        $token = $user->createToken('Personal Access Token', ['*'], Carbon::now()->addDays(4))->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|exists:users,email',
            'password' => 'nullable|string|required_without:fingerprint',
            'fingerprint' => 'nullable|string|regex:/^[a-zA-Z0-9+\/=]+$/|required_without:password',
        ]);

        $user = User::where('email', $request->email)->first();

        // Handle password login
        if ($request->filled('password')) {
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect password.',
                ], 401);
            }
        }

        // Handle fingerprint login
        elseif ($request->filled('fingerprint')) {
            if ($user->fingerprint !== $request->fingerprint) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid fingerprint.',
                ], 401);
            }
        }

        // إنشاء التوكن مع مدة صلاحية 4 أيام
        $token = $user->createToken('Personal Access Token', ['*'], Carbon::now()->addDays(4))->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
        ]);
    }
    public function update(Request $request)
{
    $request->validate([
        'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // max 2MB
    ]);

    $user = Auth::guard('sanctum')->user();

    // Optional: Delete old avatar if exists
    if ($user->avatar) {
        Storage::disk('public')->delete($user->avatar);
    }

    // Store the new avatar
    $path = $request->file('avatar')->store('avatars', 'public');

    // Update the user's avatar path
    $user->avatar = $path;
    $user->save();

    return response()->json([
        'message' => 'Avatar updated successfully.',
        'avatar_url' => asset('storage/' . $path),
    ]);
}




    public function logout(Request $request)
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Successfully logged out']);
    }






public function get(Request $request)
    {
        // Retrieve the token from the request
        $token = auth()->user();

        $imageUrl = auth()->user()->avatar ? Storage::url(auth()->user()->avatar) : null;
        // Validate the token
        if (!$token) {
            return response()->json(['error' => 'Token is required.'], 400);
        }
        // Return user data as JSON response
        return response()->json([
            'image'=>  $imageUrl ? asset($imageUrl) : null,
            'user' => $token], 200);
    }






}