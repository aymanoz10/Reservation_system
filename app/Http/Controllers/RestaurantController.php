<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\RestaurantReservation;
use App\Http\Resources\RestaurantResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class RestaurantController extends Controller
{
    public function index(Request $request) 
{
    // ✅ إذا بعت id → رجّع مطعم واحد فقط
    if ($request->has('id')) {
        $restaurant = Restaurant::with('category')->find($request->id);

        if (!$restaurant) {
            return response()->json(['error' => 'Restaurant not found'], 404);
        }

        return new RestaurantResource($restaurant);
    }

    // ✅ إذا ما في id → رجّع قائمة بالمطاعم مع البحث
    $query = Restaurant::with('category');

    if ($request->filled('search')) {
        $searchTerm = $request->search;
        $query->where(function ($q) use ($searchTerm) {
            $q->where('ar_title', 'LIKE', "%{$searchTerm}%")
              ->orWhere('en_title', 'LIKE', "%{$searchTerm}%")
              ->orWhere('location', 'LIKE', "%{$searchTerm}%");
        });
    }

    $restaurants = $query->get();

    if ($restaurants->isEmpty()) {
        return response()->json(['error' => 'No restaurants found'], 404);
    }

    return RestaurantResource::collection($restaurants);
}



public function reserve(Request $request)
{
    $user = Auth::guard('sanctum')->user();

    $request->validate([
        'restaurant_id'     => 'required|exists:restaurants,id',
        'reservation_time'  => 'required|date_format:Y-m-d H:i:s|after:now',
        'guests'            => 'required|integer|min:1',
        'area_type'         => 'nullable|in:indoor_hall,outdoor_terrace',
    ]);

    $restaurant = Restaurant::findOrFail($request->restaurant_id);

    // Check if the restaurant is currently closed at the requested reservation time
    if ($restaurant->is_closed) {
        $requestedTime = Carbon::parse($request->reservation_time);

        if ($restaurant->closed_from && $restaurant->closed_until) {
            $closedFrom = Carbon::parse($restaurant->closed_from);
            $closedUntil = Carbon::parse($restaurant->closed_until);

            if ($requestedTime->between($closedFrom, $closedUntil)) {
                return response()->json([
                    'message' => 'The restaurant is closed during the selected reservation time.',
                    'closed_from' => $closedFrom->format('Y-m-d H:i'),
                    'closed_until' => $closedUntil->format('Y-m-d H:i'),
                    'closed_duration' => $closedFrom->diffForHumans($closedUntil, [
                        'parts' => 3,
                        'short' => true,
                        'syntax' => Carbon::DIFF_ABSOLUTE,
                    ]),
                ], 409);
            }
        }
    }

    if (!$restaurant->capacity || $restaurant->capacity <= 0) {
        return response()->json([
            'success' => false,
            'message' => 'Restaurant capacity is not set properly.',
        ], 400);
    }

    $requestedTime = Carbon::parse($request->reservation_time);
    $startWindow = $requestedTime->copy()->subHours(2)->subMinutes(59);
    $endWindow = $requestedTime->copy()->addHours(2)->addMinutes(59);

    // Ignore cancelled reservations
    $existingGuestCount = RestaurantReservation::where('restaurant_id', $restaurant->id)
        ->whereBetween('reservation_time', [$startWindow, $endWindow])
        ->whereNotIn('status', ['cancelled','rejected'])
        ->sum('guests');

    if (($existingGuestCount + $request->guests) > $restaurant->capacity) {
        return response()->json([
            'success' => false,
            'message' => 'Not enough capacity for the requested number of guests in the selected time window.',
        ], 409);
    }

    $reservation = RestaurantReservation::create([
        'user_id'           => $user->id,
        'restaurant_id'     => $restaurant->id,
        'reservation_time'  => $requestedTime,
        'area_type'         => $request->area_type,
        'guests'            => $request->guests,
        'status'            => 'confirmed',
    ]);

    return response()->json([
        'success' => true,
        'reservation' => [
            'id' => $reservation->id,
            'restaurant_id' => $reservation->restaurant_id,
            'restaurant_en_title' => $restaurant->en_title,
            'restaurant_ar_title' => $restaurant->ar_title,
            'reservation_time' => $reservation->reservation_time->format('Y-m-d H:i'),
            'guests' => $reservation->guests,
            'area_type' => $reservation->area_type,
            'status' => $reservation->status,
        ],
    ]);
}


public function reservations(Request $request)
{
    $user = Auth::guard('sanctum')->user();

    // Load reservations with related restaurant and table
    $reservations = RestaurantReservation::with('restaurant')
        ->where('user_id', $user->id)
        ->orderBy('reservation_time', 'desc')
        ->get()
        ->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'restaurant_id' => $reservation->restaurant_id,
                'restaurant_en_title' => $reservation->restaurant->en_title ?? null,
                'restaurant_ar_title' => $reservation->restaurant->ar_title ?? null,
                'reservation_time' => $reservation->reservation_time->format('d/m/Y H:i'),
                'guests' => $reservation->guests,
                'area_type' => $reservation->area_type,
                'status' => $reservation->status,
            ];
        });

    return response()->json([
        'success' => true,
        'reservations' => $reservations,
    ]);
}

}
