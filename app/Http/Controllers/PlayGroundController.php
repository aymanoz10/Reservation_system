<?php

namespace App\Http\Controllers;

use App\Models\Coupons;
use Illuminate\Http\Request;
use App\Models\PlayGround;
use App\Models\PlayGroundReservation;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PlayGroundController extends Controller
{
    /**
     * عرض جميع الملاعب المتاحة
     */
   public function index(Request $request) 
{
    // ✅ إذا بعت id → رجّع ملعب واحد فقط
    if ($request->has('id')) {
        $playground = PlayGround::with('category')->find($request->id);

        if (!$playground) {
            return response()->json(['error' => 'Playground not found'], 404);
        }

        return response()->json([
            'id' => $playground->id,
            'category_id' => $playground->category_id,
            'category_name' => optional($playground->category)->en_title,
            'sport' => $playground->sport,
            'ar_title' => $playground->ar_title,
            'en_title' => $playground->en_title,
            'location' => $playground->location,
            'image' => $playground->image ? asset('storage/' . $playground->image) : null,
            'is_closed' => $playground->is_closed,
            'closed_from' => $playground->closed_from,
            'closed_until' => $playground->closed_until,
            'created_at' => $playground->created_at->format('Y-m-d H:i'),
            'updated_at' => $playground->updated_at->format('Y-m-d H:i'),
        ]);
    }

    // ✅ إذا ما بعت id → رجّع قائمة بالملاعب مع الفلاتر
    $playgrounds = PlayGround::with('category')
        ->when($request->filled('sport'), function ($query) use ($request) {
            $query->where('sport', $request->sport);
        })
        ->when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ar_title', 'LIKE', "%{$search}%")
                  ->orWhere('en_title', 'LIKE', "%{$search}%")
                  ->orWhere('location', 'LIKE', "%{$search}%");
            });
        })
        ->get();

    if ($playgrounds->isEmpty()) {
        return response()->json(['error' => 'No playgrounds found'], 404);
    }

    $data = $playgrounds->map(function ($playground) {
        return [
            'id' => $playground->id,
            'category_id' => $playground->category_id,
            'category_name' => optional($playground->category)->en_title,
            'sport' => $playground->sport,
            'ar_title' => $playground->ar_title,
            'en_title' => $playground->en_title,
            'location' => $playground->location,
            'image' => $playground->image ? asset('storage/' . $playground->image) : null,
            'is_closed' => $playground->is_closed,
            'closed_from' => $playground->closed_from,
            'closed_until' => $playground->closed_until,
            'created_at' => $playground->created_at->format('Y-m-d H:i'),
            'updated_at' => $playground->updated_at->format('Y-m-d H:i'),
        ];
    });

    return response()->json($data);
}


    /**
     * إنشاء حجز جديد للملعب
     */
    public function reserve(Request $request)
{
    $validated = $request->validate([
        'play_ground_id' => 'required|exists:play_grounds,id',
        'reservation_date' => 'required|date|after_or_equal:today',
        'reservation_time' => ['required', 'regex:/^\d{2}:\d{2}-\d{2}:\d{2}$/'],
        'payment_method' => 'required|in:cash,credit_card,MTN_CASH',
        'coupon_code' => 'nullable|string|exists:coupons,code',
    ]);

    $user = Auth::guard('sanctum')->user();
    if (!$user) {
        return response()->json(['message' => 'User not authenticated.'], 401);
    }
if ($user->is_blocked && now()->lessThan($user->blocked_until)) {
    return response()->json(['message' => 'You are currently blocked from making reservations.'], 403);
}

    // ✅ Ignore cancelled reservations
    $existingReservation = PlayGroundReservation::where('play_ground_id', $validated['play_ground_id'])
        ->where('reservation_date', $validated['reservation_date'])
        ->where('reservation_time', $validated['reservation_time'])
        ->whereNotIn('status', ['cancelled','rejected']) // <-- changed line
        ->exists();

    if ($existingReservation) {
        return response()->json([
            'message' => 'This playground is already reserved at the selected date and time.',
        ], 409);
    }

    $playground = PlayGround::findOrFail($validated['play_ground_id']);
    $price = $playground->price;
    $finalPrice = $price;
    $discountApplied = false;
    $coupon = null;

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
            return response()->json([
                'message' => 'Invalid or expired coupon.',
            ], 400);
        }
    }
   // Check if the playground is closed during the requested time range
if ($playground->is_closed && $playground->closed_from && $playground->closed_until) {
    // Extract time range from string (e.g., "14:00-15:00")
    [$startTimeStr, $endTimeStr] = explode('-', $validated['reservation_time']);

    // Combine reservation date and time into full datetime
    $startDateTime = Carbon::parse($validated['reservation_date'] . ' ' . $startTimeStr);
    $endDateTime = Carbon::parse($validated['reservation_date'] . ' ' . $endTimeStr);

    $closedFrom = Carbon::parse($playground->closed_from);
    $closedUntil = Carbon::parse($playground->closed_until);

    // Check for any overlap between reservation and closed period
    $overlaps = $startDateTime < $closedUntil && $endDateTime > $closedFrom;

    if ($overlaps) {
        return response()->json([
            'message' => 'The playground is closed during the selected reservation time.',
            'closed_from' => $closedFrom->format('Y-m-d H:i'),
            'closed_until' => $closedUntil->format('Y-m-d H:i'),
        ], 409);
    }
}
    $reservation = new PlayGroundReservation();
    $reservation->user()->associate($user);
    $reservation->playGround()->associate($playground);
    $reservation->reservation_date = $validated['reservation_date'];
    $reservation->reservation_time = $validated['reservation_time'];
    $reservation->payment_method = $validated['payment_method'];
    $reservation->price = $price;
    $reservation->final_price = $finalPrice;
    $reservation->coupons_id = $coupon ? $coupon->id : null;
    $reservation->status = 'confirmed';
    $reservation->discount_applied = $discountApplied;

    $reservation->save();

    return response()->json([
        'message' => 'Reservation created successfully',
        'reservation' => $reservation->load(['user', 'playGround']),
        'discount_applied' => $discountApplied,
        'original_price' => $price,
        'final_price' => $finalPrice,
    ], 201);
}


    /**
     * عرض حجوزات المستخدم
     */
    public function reservations(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        $reservations = PlayGroundReservation::where('user_id', $user->id)->with('playGround')->get();

        return response()->json(
            [
                'message' => 'User reservations retrieved successfully',
                'reservations' => $reservations,
            ],
            200,
        );
    }
}
