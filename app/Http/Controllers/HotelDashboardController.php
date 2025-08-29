<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Hotel;
use App\Models\HotelRoom;
use App\Models\Category;
use App\Models\HotelReservation;
class HotelDashboardController extends Controller
{
public function create(Request $request)
{
    // ✅ Check if admin is authenticated
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Validate incoming data (except category_id, which is fetched)
    $validated = $request->validate([
        'ar_title'     => 'required|string|max:255',
        'en_title'     => 'required|string|max:255',
        'image'        => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'en_location'  => 'required|string|max:255',
        'ar_location'  => 'required|string|max:255',
    ]);

    // 🔎 Fetch category_id for hotels
    $category = Category::where('en_title', 'hotels')->first();
    if (!$category) {
        return response()->json(['message' => 'Category "hotels" not found.'], 404);
    }

    // Store the image
    $path = $request->file('image')->store('hotels', 'public');

    // Create hotel
    $hotel = Hotel::create([
        'category_id'   => $category->id,
        'ar_title'      => $validated['ar_title'],
        'en_title'      => $validated['en_title'],
        'image'         => $path,
        'en_location'   => $validated['en_location'],
        'ar_location'   => $validated['ar_location'],
    ]);

    return response()->json([
        'message' => 'Hotel created successfully.',
        'hotel'   => $hotel,
    ], 201);
}


public function delete(Request $request)
{
    // ✅ Check if admin is authenticated
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized. Admin login required.'], 401);
    }

    // ✅ Validate that hotel_id exists in the request
    $request->validate([
        'hotel_id' => 'required|exists:hotels,id',
    ]);

    // ✅ Find and delete the hotel
    $hotel = Hotel::find($request->hotel_id);
    $hotel->delete();

    return response()->json([
        'message' => 'Hotel deleted successfully.',
        'hotel_id' => $request->hotel_id,
    ], 200);
}

public function reject(Request $request)
{
    // ✅ Check if admin is authenticated
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized. Admin login required.'], 401);
    }

    // ✅ Validate input
    $request->validate([
        'reservation_id' => 'required',
    ]);

    // ✅ Find and update the reservation
$reservation = HotelReservation::find($request->reservation_id);

if (!$reservation) {
    return response()->json(['message' => 'Reservation not found.'], 404);
}
    $reservation->status = 'rejected';
    $reservation->save();

    return response()->json([
        'message' => 'Reservation rejected successfully.',
        'reservation' => $reservation,
    ], 200);
}

public function close(Request $request)
{
    // Check if admin is logged in
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Validate the request
    $request->validate([
        'hotel_id'     => 'required|exists:hotels,id',
        'closed_from'  => 'required|date|after_or_equal:now',
        'closed_until' => 'required|date|after:closed_from',
    ]);

    // Fetch the hotel
    $hotel = Hotel::find($request->hotel_id);

    // Update closure fields
    $hotel->is_closed = true;
    $hotel->closed_from = $request->closed_from;
    $hotel->closed_until = $request->closed_until;
    $hotel->save();

    return response()->json([
        'message' => 'Hotel closure period set successfully.',
        'hotel'   => $hotel
    ], 200);
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
        'hotel_id' => 'required|integer',
    ]);

  $reservations = HotelReservation::with(['user', 'Room'])
    ->where('hotel_id', $request->hotel_id)
    ->orderBy('hotel_room_id') // Group by room
    ->orderBy('start_date', 'desc') // Order each room's reservations
    ->get();


    return response()->json([
        'message' => 'Reservations retrieved successfully.',
        'reservations' => $reservations,
    ]);
}

public function createRoom(Request $request)
{
    // ✅ Check if admin is authenticated
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // 🧾 Validate input
    $request->validate([
        'hotel_id'        => 'required|exists:hotels,id',
        'floor'           => 'required|integer|min:0',
        'room_number'     => 'required|integer|min:1',
        'image'           => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'type'            => 'required|string|max:255',
        'capacity'        => 'required|integer|min:1',
        'price_per_night' => 'required|numeric|min:0',
        'description'     => 'nullable|string',
    ]);
         $path = $request->file('image')->store('hotlerooms', 'public');

    // 🏨 Create room
    $room = HotelRoom::create([
        'hotel_id'        => $request->hotel_id,
        'floor'           => $request->floor,
        'room_number'     => $request->room_number,
        'image'           => $path,
        'type'            => $request->type,
        'capacity'        => $request->capacity,
        'price_per_night' => $request->price_per_night,
        'description'     => $request->description,
    ]);

    return response()->json([
        'message' => 'Hotel room created successfully.',
        'room'    => $room
    ], 201);
}


public function deleteRoom(Request $request)
{
    // ✅ Check admin authentication
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // 🧾 Validate request
    $request->validate([
        'room_id' => 'required|exists:hotel_rooms,id',
    ]);

    // 🔍 Find room
    $room = HotelRoom::find($request->room_id);

    if (!$room) {
        return response()->json(['message' => 'Hotel room not found.'], 404);
    }

    // ❌ Delete room
    $room->delete();

    return response()->json(['message' => 'Hotel room deleted successfully.'], 200);
}


}
