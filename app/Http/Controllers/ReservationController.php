<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RestaurantReservation;
use App\Models\HotelReservation;
use App\Models\ToursReservation;
use App\Models\PlayGroundReservation;
use App\Models\EventHallReservation;
use Illuminate\Support\Facades\Auth;


class ReservationController extends Controller
{
public function reservations(Request $request)
{
    $user = $request->user();

 $type = $request->input('type');
    $data = [];

    if (!$type || $type === 'restaurants') {
        $data['restaurant_reservations'] = RestaurantReservation::where('user_id', $user->id)->get();
    }

    if (!$type || $type === 'hotels') {
        $data['hotel_reservations'] = HotelReservation::where('user_id', $user->id)->get();
    }

    if (!$type || $type === 'tours') {
        $data['tour_reservations'] = ToursReservation::where('user_id', $user->id)->get();
    }

    if (!$type || $type === 'playgrounds') {
        $data['play_ground_reservations'] = PlayGroundReservation::where('user_id', $user->id)->get();
    }

    if (!$type || $type === 'event_halls') {
        $data['event_hall_reservations'] = EventHallReservation::where('user_id', $user->id)->get();
    }

    // If user passed a type that doesn't match any known type
    if ($type && empty($data)) {
        return response()->json([
            'status' => 'error',
            'message' => "Invalid reservation type: $type",
        ], 400);
    }

    return response()->json([
        'status' => 'success',
        'data' => $data,
    ]);
}


public function cancel(Request $request)
{
    $request->validate([
        'type' => 'required|in:hotel,restaurant,tour,event_hall,playground',
        'id'   => 'required|integer',
    ]);

    $user = Auth::guard('sanctum')->user();

    if ($user->is_blocked && $user->blocked_until && now()->lessThan($user->blocked_until)) {
        return response()->json(['message' => 'You are currently blocked from making or modifying reservations.'], 403);
    }

    switch ($request->type) {
        case 'hotel':
            $reservation = HotelReservation::where('id', $request->id)->where('user_id', $user->id)->first();
            break;
        case 'restaurant':
            $reservation = RestaurantReservation::where('id', $request->id)->where('user_id', $user->id)->first();
            break;
        case 'tour':
            $reservation = ToursReservation::where('id', $request->id)->where('user_id', $user->id)->first();
            break;
        case 'event_hall':
            $reservation = EventHallReservation::where('id', $request->id)->where('user_id', $user->id)->first();
            break;
        case 'playground':
            $reservation = PlaygroundReservation::where('id', $request->id)->where('user_id', $user->id)->first();
            break;
        default:
            return response()->json(['message' => 'Invalid reservation type.'], 400);
    }

    if (!$reservation) {
        return response()->json(['message' => 'Reservation not found.'], 404);
    }

    if ($reservation->status === 'cancelled') {
        return response()->json(['message' => 'Reservation already cancelled.'], 400);
    }

    // If a coupon was used, increase its used_count
   if (!empty($reservation->coupons_id)) {
    $coupon = \App\Models\Coupons::find($reservation->coupons_id);

    if ($coupon) {
        // Return one usage to the coupon
        $coupon->decrement('used_count');
    }}

    // Cancel the reservation
    $reservation->status = 'cancelled';
    $reservation->save();

    // Count cancellations in the last 3 days across all types
    $cancelCount = collect([
        HotelReservation::class,
        RestaurantReservation::class,
        ToursReservation::class,
        EventHallReservation::class,
        PlaygroundReservation::class,
    ])->sum(function ($model) use ($user) {
        return $model::where('user_id', $user->id)
            ->where('status', 'cancelled')
            ->where('updated_at', '>=', now()->subDays(3))
            ->count();
    });

    if ($cancelCount >= 3) {
        $user->is_blocked = true;
        $user->blocked_until = now()->addWeek();
        $user->save();
    }

    return response()->json([
        'message' => ucfirst($request->type) . ' reservation cancelled successfully.',
        'user_blocked' => $user->is_blocked,
        'blocked_until' => $user->blocked_until,
    ]);
}



}


