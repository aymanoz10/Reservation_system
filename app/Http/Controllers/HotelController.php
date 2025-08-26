<?php

namespace App\Http\Controllers;
use App\Models\coupons;
use Illuminate\Http\Request;
use App\Models\Hotel;
use App\Models\HotelRoom;
use App\Models\HotelReservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;



class HotelController extends Controller
{

public function index(Request $request)
{
    // إذا انبعت id → رجّع فندق واحد فقط
    if ($request->has('id')) {
        $hotel = Hotel::with('category')->find($request->id);

        if (!$hotel) {
            return response()->json(['error' => 'Hotel not found'], 404);
        }

        return response()->json([
            'id' => $hotel->id,
            'category_id' => $hotel->category_id,
            'category_name' => optional($hotel->category)->en_title,
            'ar_title' => $hotel->ar_title,
            'en_title' => $hotel->en_title,
            'image' => $hotel->image ? asset('storage/' . $hotel->image) : null,
            'ar_location' => $hotel->ar_location,
            'en_location' => $hotel->en_location,
            'is_closed' => $hotel->is_closed,
            'closed_from' => $hotel->closed_from,
            'closed_until' => $hotel->closed_until,
            'created_at' => $hotel->created_at->format('Y-m-d H:i'),
            'updated_at' => $hotel->updated_at->format('Y-m-d H:i'),
        ]);
    }

    // غير هيك → رجّع القائمة كاملة مع إمكانية البحث
    $hotels = Hotel::with('category')
        ->when($request->search, function ($query) use ($request) {
            $query->where('ar_title', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('en_title', 'LIKE', '%' . $request->search . '%');
        })->get();

    if ($hotels->isEmpty()) {
        return response()->json(['error' => 'No hotels found'], 404);
    }

    $data = $hotels->map(function ($hotel) {
        return [
            'id' => $hotel->id,
            'category_id' => $hotel->category_id,
            'category_name' => optional($hotel->category)->en_title,
            'ar_title' => $hotel->ar_title,
            'en_title' => $hotel->en_title,
            'image' => $hotel->image ? asset('storage/' . $hotel->image) : null,
            'ar_location' => $hotel->ar_location,
            'en_location' => $hotel->en_location,
            'is_closed' => $hotel->is_closed,
            'closed_from' => $hotel->closed_from,
            'closed_until' => $hotel->closed_until,
            'created_at' => $hotel->created_at->format('Y-m-d H:i'),
            'updated_at' => $hotel->updated_at->format('Y-m-d H:i'),
        ];
    });

    return response()->json($data);
}



public function rooms(Request $request)
{
    $request->validate([
        'hotel_id' => 'required|integer|exists:hotels,id',
    ]);

    $hotel = Hotel::with('rooms')->find($request->hotel_id);

    if (!$hotel) {
        return response()->json([
            'success' => false,
            'message' => 'Hotel not found.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'hotel' => [
            'id' => $hotel->id,
            'ar_title' => $hotel->ar_title,
            'en_title' => $hotel->en_title,

        ],
        'rooms' => $hotel->rooms,
    ]);
}






public function reserve(Request $request)
{
    $user = Auth::guard('sanctum')->user();

    $request->validate([
        'hotel_id'        => 'required|exists:hotels,id',
        'room_number'     => 'required|integer',
        'start_date'      => 'required|date|after_or_equal:today',
        'nights'          => 'required|integer|min:1',
        'payment_method'  => 'nullable|in:cash,credit_card,paypal',
        'coupon_code'     => 'nullable|string|exists:coupons,code',
    ]);

    if ($user->is_blocked && now()->lessThan($user->blocked_until)) {
        return response()->json(['message' => 'You are currently blocked from making reservations.'], 403);
    }

    $startDate = Carbon::parse($request->start_date);
    $endDate = $startDate->copy()->addDays($request->nights);

    // ✅ Hotel closure check
    $hotel = Hotel::find($request->hotel_id);
    if ($hotel && $hotel->is_closed) {
        if (
            $hotel->closed_from && $hotel->closed_until &&
            $startDate < $hotel->closed_until && $endDate > $hotel->closed_from
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This hotel is temporarily closed during your selected dates.',
            ], 403);
        }
    }

    // Room lookup
    $room = HotelRoom::where('hotel_id', $request->hotel_id)
                     ->where('room_number', $request->room_number)
                     ->whereNotNull('price_per_night')
                     ->first();

    if (!$room) {
        return response()->json([
            'success' => false,
            'message' => 'Room number not found in this hotel.',
        ], 404);
    }

    // Conflict check
    $conflict = HotelReservation::where('hotel_room_id', $room->id)
        ->whereNotIn('status', ['cancelled', 'rejected'])
        ->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate, $endDate->copy()->subDay()])
                  ->orWhereRaw('? BETWEEN start_date AND DATE_ADD(start_date, INTERVAL nights - 1 DAY)', [$startDate]);
        })
        ->exists();

    if ($conflict) {
        return response()->json([
            'success' => false,
            'message' => 'This room is already reserved during the selected period.',
        ], 409);
    }

    $price = $room->price_per_night;
    $final_price = $price * $request->nights;
    $coupon = null;

    if ($request->filled('coupon_code')) {
        $coupon = Coupons::where('code', $request->coupon_code)->first();

        if ($coupon && $coupon->is_active && $coupon->expires_at >= now() && $coupon->used_count < $coupon->usage_limit) {
            $discount_amount = $final_price * ($coupon->discount_percentage / 100);
            $final_price = max(0, $final_price - $discount_amount);

            $coupon->increment('used_count');
            $coupon->user_id = $user->id;
            $coupon->used_at = now();
            $coupon->save();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired coupon.',
            ], 400);
        }
    }

    $reservation = HotelReservation::create([
        'user_id'        => $user->id,
        'hotel_id'       => $hotel->id,
        'hotel_room_id'  => $room->id,
        'start_date'     => $startDate,
        'nights'         => $request->nights,
        'payment_method' => $request->payment_method,
        'price'          => $price,
        'final_price'    => $final_price,
        'coupons_id'     => $coupon ? $coupon->id : null,
        'status'         => 'confirmed',
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Room reserved successfully.',
        'reservation' => [
            'reservation_id'  => $reservation->id,
            'start_date'      => $reservation->start_date->toDateString(),
            'nights'          => $reservation->nights,
            'payment_method'  => $reservation->payment_method,
            'status'          => $reservation->status,
            'total_price'     => $reservation->final_price,
        ],
    ]);
}




public function reservations()
{
    $user = Auth::guard('sanctum')->user();

    $reservations = HotelReservation::with(['hotel', 'room'])
        ->where('user_id', $user->id)
        ->get()
        ->map(function ($reservation) {
            return [
                'reservation_id' => $reservation->id,
                'hotel_name' => $reservation->hotel->name ?? null,
                'room_number' => $reservation->room->room_number ?? null,
                'floor' => $reservation->room->floor ?? null,
                'start_date' => $reservation->start_date->toDateString(),
                'nights' => $reservation->nights,
                'payment_method' => $reservation->payment_method,

                'status' => $reservation->status,
            ];
        });

    return response()->json([
        'success' => true,
        'reservations' => $reservations,
    ]);
}

}
