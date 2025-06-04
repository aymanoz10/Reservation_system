<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupons;
use App\Models\RestaurantReservation;
use App\Models\HotelReservation;
use App\Models\ToursReservation;
use App\Models\PlayGroundReservation;
use App\Models\EventHallReservation;
use Illuminate\Support\Facades\Auth;



class AdminDashboardController extends Controller
{

public function users()
{
    $users = \App\Models\User::select('id', 'first_name', 'last_name', 'username', 'email', 'is_blocked', 'blocked_until', 'created_at')
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'success' => true,
        'users' => $users,
    ]);
}

public function blockedUsers()
{
    $blockedUsers = \App\Models\User::where('is_blocked', true)
        ->select('id', 'first_name', 'last_name', 'username', 'email', 'blocked_until', 'created_at')
        ->orderBy('blocked_until', 'desc')
        ->get();

    return response()->json([
        'success' => true,
        'blocked_users' => $blockedUsers,
    ]);
}


public function unblock(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    $user = \App\Models\User::where('email', $request->email)->first();
    $user->is_blocked = false;
    $user->blocked_until = null;
    $user->save();

    return response()->json(['message' => 'User has been unblocked successfully.']);
}

public function coupons(Request $request)
{
    $coupons = Coupons::all();

    return response()->json([
        'status' => 'success',
        'coupons' => $coupons
    ], 200);
}

public function createCoupon(Request $request)
{
    // ✅ Ensure admin is logged in
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // ✅ Validate input
    $validated = $request->validate([
        'code'               => 'required|string|unique:coupons,code',
        'discount_percentage'=> 'required|integer|min:1|max:100',
        'usage_limit'        => 'nullable|integer|min:1',
        'expires_at'         => 'nullable|date|after_or_equal:now',
        'is_active'          => 'nullable|boolean',
    ]);

    // ✅ Create the coupon
    $coupon = Coupons::create([
        'code'               => $validated['code'],
        'discount_percentage'=> $validated['discount_percentage'],
        'usage_limit'        => $validated['usage_limit'] ?? 1,
        'used_count'         => 0,
        'expires_at'         => $validated['expires_at'] ?? null,
        'is_active'          => $validated['is_active'] ?? true,
    ]);

    return response()->json([
        'message' => 'Coupon created successfully',
        'coupon'  => $coupon,
    ], 201);
}



public function userReservations(Request $request)
{
    // ✅ Check if admin is logged in
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // ✅ Validate input
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'type' => 'nullable|string|in:restaurants,hotels,tours,playgrounds,event_halls',
    ]);

    $userId = $validated['user_id'];
    $type = $validated['type'] ?? null;

    $data = [];

    if (!$type || $type === 'restaurants') {
        $data['restaurant_reservations'] = \App\Models\RestaurantReservation::where('user_id', $userId)->get();
    }

    if (!$type || $type === 'hotels') {
        $data['hotel_reservations'] = \App\Models\HotelReservation::where('user_id', $userId)->get();
    }

    if (!$type || $type === 'tours') {
        $data['tour_reservations'] = \App\Models\ToursReservation::where('user_id', $userId)->get();
    }

    if (!$type || $type === 'playgrounds') {
        $data['play_ground_reservations'] = \App\Models\PlayGroundReservation::where('user_id', $userId)->get();
    }

    if (!$type || $type === 'event_halls') {
        $data['event_hall_reservations'] = \App\Models\EventHallReservation::where('user_id', $userId)->get();
    }

    return response()->json([
        'status' => 'success',
        'data' => $data,
    ]);
}


}
