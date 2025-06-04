<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\Restaurant;
use App\Models\RestaurantReservation;
use Carbon\Carbon;


class RestaurantDashboardController extends Controller
{
public function create(Request $request)
{

    $validated = $request->validate([
        'ar_title'      => 'required|string|max:255',
        'en_title'      => 'required|string|max:255',
        'image'         => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'capacity'      => 'required|integer|min:1',
        'ar_location'   => 'required|string|max:255',
        'en_location'   => 'required|string|max:255',
    ]);
    $admin = Auth::guard('admin')->user();
    if (!$admin) {
        return response()->json(['message' => 'Unauthorized.'], 401);
    }
    // Store the uploaded image
    $path = $request->file('image')->store('restaurants', 'public');
  // Automatically get the category_id for the "restaurants" category
    $category = Category::where('en_title', 'restaurants')->orWhere('ar_title', 'مطاعم')->first();

    if (!$category) {
        return response()->json(['message' => 'Category for restaurants not found.'], 404);
    }
    // Create the restaurant
    $restaurant = \App\Models\Restaurant::create([
        'category_id'   => $category->id,
        'ar_title'      => $validated['ar_title'],
        'en_title'      => $validated['en_title'],
        'image'         => $path,
        'capacity'      => $validated['capacity'],
        'ar_location'   => $validated['ar_location'],
        'en_location'   => $validated['en_location'],
    ]);

    return response()->json([
        'message' => 'Restaurant created successfully.',
        'restaurant' => $restaurant,
    ], 201);
}


public function delete(Request $request)
{
    $admin = Auth::guard('admin')->user();
    if (!$admin) {
        return response()->json(['message' => 'Unauthorized.'], 401);
    }

    $request->validate([
        'restaurant_id' => 'required|integer|exists:restaurants,id',
    ]);

    $restaurant = Restaurant::find($request->restaurant_id);

    // Optionally delete the image from storage
    if ($restaurant->image && Storage::disk('public')->exists($restaurant->image)) {
        Storage::disk('public')->delete($restaurant->image);
    }

    $restaurant->delete();

    return response()->json(['message' => 'Restaurant deleted successfully.'], 200);
}


public function reservations(Request $request)
{
    // Check admin authentication
    $admin = Auth::guard('admin')->user();
    if (!$admin) {
        return response()->json(['message' => 'Unauthorized. Admin login required.'], 401);
    }

    // Validate request
    $request->validate([
        'restaurant_id' => 'required|integer|exists:restaurants,id',
    ]);

    // Fetch reservations
    $reservations = RestaurantReservation::with('user')
        ->where('restaurant_id', $request->restaurant_id)
        ->orderBy('reservation_time', 'desc')
        ->get();

    return response()->json([
        'message' => 'Reservations retrieved successfully.',
        'reservations' => $reservations,
    ]);
}


 public function close(Request $request)
    {
        // Check if admin is logged in (assuming 'admin' guard)
        if (!Auth::guard('admin')->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Validate request input
        $validated = $request->validate([
            'restaurant_id' => 'required|integer|exists:restaurants,id',
            'closed_from' => 'required|date',
            'closed_until' => 'required|date|after_or_equal:closed_from',
        ]);

        $restaurant = Restaurant::find($validated['restaurant_id']);

        // Update closing info
        $restaurant->is_closed = true;
        $restaurant->closed_from = Carbon::parse($validated['closed_from']);
        $restaurant->closed_until = Carbon::parse($validated['closed_until']);
        $restaurant->save();

        return response()->json(['message' => 'Restaurant closed successfully']);
    }

    public function reject(Request $request)
{
    // Check if admin is logged in
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Validate input
    $request->validate([
        'reservation_id' => 'required|integer|exists:restaurant_reservations,id',
    ]);

    $reservation = RestaurantReservation::find($request->reservation_id);

    if (!$reservation) {
        return response()->json(['message' => 'Reservation not found'], 404);
    }

    // Update status to rejected only if not already rejected or cancelled
    if (in_array($reservation->status, ['rejected', 'cancelled'])) {
        return response()->json(['message' => 'Reservation is already ' . $reservation->status], 400);
    }

    $reservation->status = 'rejected';
    $reservation->save();

    return response()->json([
        'success' => true,
        'message' => 'Reservation rejected successfully',
        'reservation_id' => $reservation->id,
        'new_status' => $reservation->status,
    ]);
}

}
