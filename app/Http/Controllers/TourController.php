<?php

namespace App\Http\Controllers;

use App\Models\coupons;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use App\Models\Tour;
use App\Models\TourStop;
use App\Models\ToursReservation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class TourController extends Controller
{


    public function index(Request $request)
    {
        $tours = Tour::when($request->search, function ($query) use ($request) {
            $query->where('ar_title', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('en_title', 'LIKE', '%' . $request->search . '%');
        })->get();

        $result = $tours->map(function ($tour) {
            return [
                'id' => $tour->id,
                'category_id' => $tour->category_id,
                'ar_title' => $tour->ar_title,
                'en_title' => $tour->en_title,
                'ar_description' => $tour->ar_description,
                'en_description' => $tour->en_description,
                'image' => $tour->image ? asset(Storage::url($tour->image)) : null,
                'price' => number_format($tour->price, 2),
                'start_date' => $tour->start_date->format('Y-m-d'),
                'end_date' => $tour->end_date->format('Y-m-d'),
                'created_at' => $tour->created_at->format('Y-m-d H:i'),
                'updated_at' => $tour->updated_at->format('Y-m-d H:i'),
            ];
        });

        return response()->json($result);
    }



    public function stops(Request $request)

    {

        // Load tour with its stops
        $tour = Tour::with('stops')->find($request->tour_id);

        // Format the response
        $stops = $tour->stops->map(function ($stop) {
            return [
                'id' => $stop->id,
                'sequence' => $stop->sequence,
                'ar_title' => $stop->ar_title,
                'en_title' => $stop->en_title,
                'image' => $stop->image ? asset(Storage::url($stop->image)) : null,
                'ar_description' => $stop->ar_description,
                'en_description' => $stop->en_description,
                'created_at' => $stop->created_at->toDateTimeString(),
                'updated_at' => $stop->updated_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'tour_id' => $tour->id,
            'ar_title' => $tour->ar_title,
            'en_title' => $tour->en_title,
            'stops' => $stops,
        ]);
    }


 public function reserve(Request $request)
{
    // التحقق من صحة البيانات المدخلة
    $validated = $request->validate([
        'tour_id'          => 'required|exists:tours,id',
        'guests'           => 'required|integer|min:1',
        'payment_method'   => 'required|in:cash,paypal,credit_card',
        'coupon_code'      => 'nullable|string|exists:coupons,code', // التحقق من صحة الكوبون
    ]);

    // جلب المستخدم المسجل
    $user = Auth::guard('sanctum')->user();
    if (!$user) {
        return response()->json(['message' => 'User not authenticated.'], 401);
    }
if ($user->is_blocked && now()->lessThan($user->blocked_until)) {
    return response()->json(['message' => 'You are currently blocked from making reservations.'], 403);
}

    // جلب بيانات الرحلة
    $tour = Tour::findOrFail($validated['tour_id']);

    $startDate = Carbon::parse($tour->start_date)->format('Y-m-d');
    $endDate = Carbon::parse($tour->end_date)->format('Y-m-d');

    // التحقق من وجود حجز بنفس الفترة الزمنية مع تجاهل المحجوزات الملغاة
    $existingReservation = ToursReservation::where('tour_id', $tour->id)
        ->where('start_date', $startDate)
        ->where('end_date', $endDate)
        ->whereNotIn('status', ['cancelled']) // ✅ تجاهل الحجوزات الملغاة
        ->exists();

    if ($existingReservation) {
        return response()->json([
            'message' => 'This tour is already reserved for the selected date range.',
        ], 409);
    }

    $price = $tour->price * $validated['guests'];
    $finalPrice = $price;
    $discountApplied = false;
    $couponId = null;

    $coupon = null;

    // معالجة الكوبون إذا وجد
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
            $couponId = $coupon->id;
        } else {
            return response()->json([
                'message' => 'Invalid or expired coupon.',
            ], 400);
        }
    }

    // إنشاء الحجز
    $reservation = new ToursReservation();
    $reservation->user()->associate($user);
    $reservation->tour()->associate($tour);
    $reservation->guests = $validated['guests'];
    $reservation->payment_method = $validated['payment_method'];
    $reservation->price = $price;
    $reservation->final_price = $finalPrice;
    $reservation->coupons_id = $couponId;
    $reservation->status = 'done';
    $reservation->discount_applied = $discountApplied;
    $reservation->start_date = $startDate;
    $reservation->end_date = $endDate;

    $reservation->save();

    return response()->json([
        'message' => 'Reservation created successfully.',
        'reservation' => $reservation->load(['user', 'tour']),
        'discount_applied' => $discountApplied,
        'original_price' => $price,
        'final_price' => $finalPrice
    ], 201);
}




public function reservations(Request $request)
{
    $user = Auth::guard('sanctum')->user();

    $reservations = ToursReservation::with('tour')
        ->where('user_id', $user->id)
        ->get()
        ->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'tour_id' => $reservation->tour_id,
                'tour_title_ar' => $reservation->tour->ar_title ?? null,
                'tour_title_en' => $reservation->tour->en_title ?? null,
                'guests' => $reservation->guests,
                'price' => $reservation->price,
                'status' => $reservation->status,
                'payment_method' => $reservation->payment_method,
                'start_date' => Carbon::parse($reservation->start_date)->format('d/m/Y'),
                'end_date' => Carbon::parse($reservation->end_date)->format('d/m/Y'),
                'created_at' => $reservation->created_at->format('d/m/Y'),
                'updated_at' => $reservation->updated_at->format('d/m/Y'),
            ];
        });

    return response()->json([
        'success' => true,
        'reservations' => $reservations,
    ]);
}


    }
