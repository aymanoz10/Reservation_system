<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Coupons;
use Illuminate\Http\Request;
use App\Models\EventHall;
use App\Models\EventHallReservation;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EventHallController extends Controller
{
    public function index(Request $request) 
{
    // إذا في id → رجّع القاعة المطلوبة فقط
    if ($request->has('id')) {
        $eventHall = EventHall::with('category')->find($request->id);

        if (!$eventHall) {
            return response()->json(['error' => 'Event hall not found'], 404);
        }

        return response()->json([
            'id' => $eventHall->id,
            'category_id' => $eventHall->category_id,
            'category_name' => optional($eventHall->category)->en_title,
            'ar_title' => $eventHall->ar_title,
            'en_title' => $eventHall->en_title,
            'image' => $eventHall->image ? asset('storage/' . $eventHall->image) : null,
            'price' => $eventHall->price,
            'capacity' => $eventHall->capicity,
            'is_closed' => $eventHall->is_closed,
            'closed_from' => $eventHall->closed_from,
            'closed_until' => $eventHall->closed_until,
            'ar_location' => $eventHall->ar_location,
            'en_location' => $eventHall->en_location,
            'created_at' => $eventHall->created_at->format('Y-m-d H:i'),
            'updated_at' => $eventHall->updated_at->format('Y-m-d H:i'),
        ]);
    }

    // إذا ما في id → رجّع كل القاعات مع البحث
    $eventHalls = EventHall::with('category')
        ->when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ar_title', 'LIKE', "%{$search}%")
                  ->orWhere('en_title', 'LIKE', "%{$search}%")
                  ->orWhere('ar_location', 'LIKE', "%{$search}%")
                  ->orWhere('en_location', 'LIKE', "%{$search}%");
            });
        })
        ->get();

    if ($eventHalls->isEmpty()) {
        return response()->json(['error' => 'No event halls found'], 404);
    }

    // رجّع القائمة كلها
    $data = $eventHalls->map(function ($eventHall) {
        return [
            'id' => $eventHall->id,
            'category_id' => $eventHall->category_id,
            'category_name' => optional($eventHall->category)->en_title,
            'ar_title' => $eventHall->ar_title,
            'en_title' => $eventHall->en_title,
            'image' => $eventHall->image ? asset('storage/' . $eventHall->image) : null,
            'price' => $eventHall->price,
            'capacity' => $eventHall->capicity,
            'is_closed' => $eventHall->is_closed,
            'closed_from' => $eventHall->closed_from,
            'closed_until' => $eventHall->closed_until,
            'ar_location' => $eventHall->ar_location,
            'en_location' => $eventHall->en_location,
            'created_at' => $eventHall->created_at->format('Y-m-d H:i'),
            'updated_at' => $eventHall->updated_at->format('Y-m-d H:i'),
        ];
    });

    return response()->json($data);
}

 
public function reserve(Request $request)
{
    $user = Auth::guard('sanctum')->user();
    if (!$user) {
        return response()->json(['message' => 'User not authenticated.'], 401);
    }

    $validated = $request->validate([
        'event_hall_id'    => 'required|exists:event_halls,id',
        'event_type'       => 'required|in:wedding,funeral',
        'reservation_date' => 'required|date|after_or_equal:today',
        'reservation_time' => ['required', 'regex:/^\d{2}:\d{2}-\d{2}:\d{2}$/'],
        'guests'           => 'required|integer|min:1',
        'payment_method'   => 'required|in:cash,credit_card,MTN_CASH',
        'coupon_code'      => 'nullable|string|exists:coupons,code',
    ]);

    // Blocked user check
    if ($user->is_blocked && now()->lessThan($user->blocked_until)) {
        return response()->json(['message' => 'You are currently blocked from making reservations.'], 403);
    }

    $eventHall = EventHall::findOrFail($validated['event_hall_id']);
    $price = $eventHall->price;
    $finalPrice = $price;
    $discountApplied = false;
    $coupon = null;

    // Check if the hall is closed on the requested date
    if ($eventHall->is_closed && $eventHall->closed_from && $eventHall->closed_until) {
        $reservationDate = Carbon::parse($validated['reservation_date']);

        if ($reservationDate->between($eventHall->closed_from, $eventHall->closed_until)) {
            return response()->json([
                'message' => 'The event hall is closed on the selected date.',
                'closed_from' => Carbon::parse($eventHall->closed_from)->toDateString(),
                'closed_until' => Carbon::parse($eventHall->closed_until)->toDateString(),
            ], 409);
        }
    }

    // Check if the time slot is already booked (only confirmed/done block it)
    $existingReservation = EventHallReservation::where('event_hall_id', $eventHall->id)
        ->where('reservation_date', $validated['reservation_date'])
        ->where('reservation_time', $validated['reservation_time'])
        ->whereIn('status', ['confirmed', 'done'])
        ->exists();

    if ($existingReservation) {
        return response()->json([
            'message' => 'Sorry, this time slot is already booked.',
        ], 409);
    }

    // Capacity check
    if ($validated['guests'] > $eventHall->capicity) {
        return response()->json([
            'message' => "Cannot reserve. The hall's maximum capacity is {$eventHall->capicity} guests.",
        ], 409);
    }

    // Coupon check
    if (!empty($validated['coupon_code'])) {
        $coupon = Coupons::where('code', $validated['coupon_code'])
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->whereColumn('used_count', '<', 'usage_limit')
            ->first();

        if ($coupon) {
            $discountAmount = $price * ($coupon->discount_percentage / 100);
            $finalPrice = max(0, $price - $discountAmount);
            $coupon->increment('used_count');
            $discountApplied = true;
        } else {
            return response()->json(['message' => 'Invalid or expired coupon.'], 400);
        }
    }

    // Create reservation
    $reservation = new EventHallReservation();
    $reservation->user()->associate($user);
    $reservation->eventHall()->associate($eventHall);
    $reservation->event_type = $validated['event_type'];
    $reservation->reservation_date = $validated['reservation_date'];
    $reservation->reservation_time = $validated['reservation_time'];
    $reservation->guests = $validated['guests'];
    $reservation->payment_method = $validated['payment_method'];
    $reservation->price = $price;
    $reservation->final_price = $finalPrice;
    $reservation->coupons_id = $coupon ? $coupon->id : null;
    $reservation->status = 'confirmed';
    $reservation->discount_applied = $discountApplied;
    $reservation->save();

    return response()->json([
        'message' => 'Reservation created successfully.',
        'reservation' => $reservation->load(['user', 'eventHall']),
        'discount_applied' => $discountApplied,
        'original_price' => $price,
        'final_price' => $finalPrice,
    ], 201);
}

    

    /**
     * جلب حجوزات المستخدم
     */
    public function reservations(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        $reservations = EventHallReservation::where('user_id', $user->id)->get();

        return response()->json([
            'status' => 'success',
            'data' => $reservations,
        ]);
    }
}
