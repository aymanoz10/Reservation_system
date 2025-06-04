<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\EventHall;
use App\Models\EventHallReservation;
use Carbon\Carbon;

class EventHallDashboardController extends Controller
{


public function create(Request $request)
{
    // ✅ Check if admin is authenticated
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // ✅ Validate input data
    $validated = $request->validate([
        'ar_title'     => 'required|string|max:255',
        'en_title'     => 'required|string|max:255',
        'image'         => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'en_location'  => 'required|string|max:255',
        'ar_location'  => 'required|string|max:255',
        'capicity'     => 'required|integer|min:1',
        'price'        => 'required|integer|min:0',
    ]);

    // ✅ Fetch the category for event halls
    $category = Category::where('en_title', 'event_halls')
                        ->orWhere('ar_title', ' صالات المناسبات')
                        ->first();

    if (!$category) {
        return response()->json(['message' => 'Event Hall category not found.'], 404);
    }
            // Store the uploaded image
    $path = $request->file('image')->store('restaurants', 'public');

    // ✅ Create new event hall record
    $eventHall = EventHall::create([
        'category_id'  => $category->id,
        'ar_title'     => $validated['ar_title'],
        'en_title'     => $validated['en_title'],
        'image'        => $path,
        'en_location'  => $validated['en_location'],
        'ar_location'  => $validated['ar_location'],
        'capicity'     => $validated['capicity'],
        'price'        => $validated['price'],
    ]);

    return response()->json([
        'success'    => true,
        'event_hall' => $eventHall,
    ], 201);
}

public function delete(Request $request)
{
    // Check if admin is logged in
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Validate input
    $request->validate([
        'event_hall_id' => 'required',
    ]);

    $eventhall = EventHall::find($request->event_hall_id);

    if (!$eventhall) {
        return response()->json(['message' => 'EventHall not found.'], 404);
    }

    $eventhall->delete();

    return response()->json([
        'success' => true,
        'message' => 'EventHall deleted successfully.',
    ]);
}


public function reject(Request $request)
{
    // Check if admin is logged in
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Validate input
    $request->validate([
        'reservation_id' => 'required|integer',
    ]);

    $reservation = EventHallReservation::find($request->reservation_id);

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

public function close(Request $request)
    {
        // Check if admin is logged in (assuming 'admin' guard)
        if (!Auth::guard('admin')->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Validate request input
        $validated = $request->validate([
            'event_hall_id' => 'required|integer',
            'closed_from' => 'required|date',
            'closed_until' => 'required|date|after_or_equal:closed_from',
        ]);

        $playground = EventHall::find($validated['event_hall_id']);

        // Update closing info
        $playground->is_closed = true;
        $playground->closed_from = Carbon::parse($validated['closed_from']);
        $playground->closed_until = Carbon::parse($validated['closed_until']);
        $playground->save();

        return response()->json(['message' => 'playGround closed successfully']);
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
        'event_hall_id' => 'required|integer',
    ]);

    // Fetch reservations
    $reservations = EventHallReservation::with('user')
        ->where('event_hall_id', $request->event_hall_id)
        ->orderBy('reservation_time', 'desc')
        ->get();

    return response()->json([
        'message' => 'Reservations retrieved successfully.',
        'reservations' => $reservations,
    ]);
}
}
