<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tour;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use App\Models\ToursReservation;
use App\Models\TourStop;

class TourDashboardController extends Controller
{
public function create(Request $request)
{
   // ✅ Check if admin is authenticated
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }
    // Validate input except category_id
    $validated = $request->validate([
        'ar_title'         => 'required|string|max:255',
        'en_title'         => 'required|string|max:255',
        'ar_description'   => 'required|string',
        'en_description'   => 'required|string',
        'image'            => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'price'            => 'required|numeric|min:0',
        'start_date'       => 'required|date|after_or_equal:today',
        'end_date'         => 'required|date|after_or_equal:start_date',
    ]);

    // Fetch the category_id internally
    $category = Category::where('en_title', 'tours')->first();
    if (!$category) {
        return response()->json(['message' => 'Category "tours" not found.'], 404);
    }
        $path = $request->file('image')->store('restaurants', 'public');


    // Add category_id to validated data
    $validated['category_id'] = $category->id;

    // Create the Tour
    $tour = Tour::create([
        'category_id'    => $category->id,
        'ar_title'       => $validated['ar_title'],
        'en_title'       => $validated['en_title'],
        'ar_description' => $validated['ar_description'],
        'en_description' => $validated['en_description'],
        'image'          => $path,
        'price'          => $validated['price'],
        'start_date'     => $validated['start_date'],
        'end_date'     => $validated['end_date'],
    ]);

    return response()->json([
        'message' => 'Tour created successfully.',
        'tour' => $tour
    ], 201);
}

public function delete(Request $request)
{
    // Check if admin is authenticated
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Validate tour_id
    $request->validate([
        'tour_id' => 'required|integer|exists:tours,id',
    ]);

    // Find the Tour by ID
    $tour = Tour::find($request->tour_id);

    if (!$tour) {
        return response()->json(['message' => 'Tour not found.'], 404);
    }

    // Delete the tour
    $tour->delete();

    return response()->json(['message' => 'Tour deleted successfully.'], 200);
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

    $reservation = ToursReservation::find($request->reservation_id);

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

public function reservations(Request $request)
{
    // Check admin authentication
    $admin = Auth::guard('admin')->user();
    if (!$admin) {
        return response()->json(['message' => 'Unauthorized. Admin login required.'], 401);
    }

    // Validate request
    $request->validate([
        'tour_id' => 'required|integer',
    ]);

    // Fetch reservations
    $reservations = ToursReservation::with('user')
        ->where('tour_id', $request->tour_id)
        ->get();

    return response()->json([
        'message' => 'Reservations retrieved successfully.',
        'reservations' => $reservations,
    ]);
}




public function createStop(Request $request)
{
    // ✅ Check if admin is authenticated
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // ✅ Validate required input
    $request->validate([
        'tour_id'         => 'required|exists:tours,id',
        'ar_title'        => 'required|string|max:255',
        'en_title'        => 'required|string|max:255',
        'ar_description'  => 'required|string',
        'en_description'  => 'required|string',
        'image'           => 'required|image|max:2048', // image file validation
    ]);

    // ✅ Handle image upload
    $path = $request->file('image')->store('tour_stops', 'public');

    // ✅ Get next sequence number
    $lastSequence = TourStop::where('tour_id', $request->tour_id)->max('sequence');
    $nextSequence = $lastSequence ? $lastSequence + 1 : 1;

    // ✅ Create the Tour Stop
    $tourStop = TourStop::create([
        'tour_id'         => $request->tour_id,
        'sequence'        => $nextSequence,
        'ar_title'        => $request->ar_title,
        'en_title'        => $request->en_title,
        'image'           => $path,
        'ar_description'  => $request->ar_description,
        'en_description'  => $request->en_description,
    ]);

    return response()->json([
        'message' => 'Tour stop created successfully.',
        'stop'    => $tourStop,
    ], 201);
}

public function deleteStop(Request $request)
{
    // ✅ Check if admin is authenticated
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Validate input
    $validated = $request->validate([
        'tour_stop_id' => 'required',
    ]);

    // Fetch the tour stop
    $stop = TourStop::find($validated['tour_stop_id']);

    if (!$stop) {
        return response()->json(['message' => 'Tour stop not found.'], 404);
    }

    $tourId = $stop->tour_id;
    $sequenceToRemove = $stop->sequence;

    // Delete the stop
    $stop->delete();

    // Decrement sequence of later stops in the same tour
    TourStop::where('tour_id', $tourId)
        ->where('sequence', '>', $sequenceToRemove)
        ->decrement('sequence');

    return response()->json(['message' => 'Tour stop deleted and sequences updated.'], 200);
}

}
