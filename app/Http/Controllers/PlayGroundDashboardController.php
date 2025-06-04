<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PlayGround;
use App\Models\PlayGroundReservation;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class PlayGroundDashboardController extends Controller
{
public function create(Request $request)
{
    // Check if admin is logged in
    $admin = Auth::guard('admin')->user();
    if (!$admin) {
        return response()->json(['message' => 'Unauthorized.'], 401);
    }

    // Validate the request data
    $request->validate([
        'sport'        => 'required|in:Football,Basketball,Tennis',
        'ar_title'     => 'required|string|max:255',
        'en_title'     => 'required|string|max:255',
        'image'         => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'en_location'  => 'required|string|max:255',
        'ar_location'  => 'required|string|max:255',
        'price'        => 'required|integer|min:0',
        'capicity'     => 'required|integer|min:1',
    ]);
        $category = Category::where('en_title', 'play_grounds')->orWhere('ar_title', 'ملاعب')->first();
        if (!$category) {
        return response()->json(['message' => 'Category for play_grounds not found.'], 404);
         }

          // Store the uploaded image
    $path = $request->file('image')->store('restaurants', 'public');

    $playground = PlayGround::create([
        'category_id'   => $category->id,
        'sport'        => $request->sport,
        'ar_title'     => $request->ar_title,
        'en_title'     => $request->en_title,
        'image'         => $path,
        'en_location'  => $request->en_location,
        'ar_location'  => $request->ar_location,
        'price'        => $request->price,
        'capicity'     => $request->capicity,
    ]);

    return response()->json([
        'success' => true,
        'playground' => $playground,
    ]);
}
public function delete(Request $request)
{
    $admin = Auth::guard('admin')->user();
    if (!$admin) {
        return response()->json(['message' => 'Unauthorized.'], 401);
    }

    $request->validate([
        'play_ground_id' => 'required|integer|exists:play_grounds,id',
    ]);

    $playground = PlayGround::find($request->play_ground_id);

    // Optionally delete the image from storage
    if ($playground->image && Storage::disk('public')->exists($playground->image)) {
        Storage::disk('public')->delete($playground->image);
    }

    $playground->delete();

    return response()->json(['message' => 'playGround deleted successfully.'], 200);
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

    $reservation = PlayGroundReservation::find($request->reservation_id);

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
            'play_ground_id' => 'required|integer',
            'closed_from' => 'required|date',
            'closed_until' => 'required|date|after_or_equal:closed_from',
        ]);

        $playground = PlayGround::find($validated['play_ground_id']);

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
        'play_ground_id' => 'required|integer',
    ]);

    // Fetch reservations
    $reservations = PlayGroundReservation::with('user')
        ->where('play_ground_id', $request->play_ground_id)
        ->orderBy('reservation_time', 'desc')
        ->get();

    return response()->json([
        'message' => 'Reservations retrieved successfully.',
        'reservations' => $reservations,
    ]);
}
}
