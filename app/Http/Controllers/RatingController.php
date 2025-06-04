<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{


public function rate(Request $request)
{
    $request->validate([
        'type' => 'required|in:hotel,playground,tour,restaurant,event_hall',
        'id' => 'required|integer',
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string',
    ]);

    $user = Auth::guard('sanctum')->user();

    $modelMap = [
        'hotel' => \App\Models\Hotel::class,
        'playground' => \App\Models\Playground::class,
        'tour' => \App\Models\Tour::class,
        'restaurant' => \App\Models\Restaurant::class,
        'event_hall' => \App\Models\EventHall::class,
    ];

    $modelClass = $modelMap[$request->type] ?? null;

    if (!$modelClass) {
        return response()->json(['message' => 'Invalid type.'], 400);
    }

    $modelInstance = $modelClass::find($request->id);
    if (!$modelInstance) {
        return response()->json(['message' => 'Target not found.'], 404);
    }

    // Check if user already rated this place
    $alreadyRated = \App\Models\Rating::where('user_id', $user->id)
        ->where('rateable_type', $modelClass)
        ->where('rateable_id', $request->id)
        ->exists();

    if ($alreadyRated) {
        return response()->json(['message' => 'You have already rated this place.'], 409);
    }

    // Save new rating
    $rating = new Rating();
    $rating->rating = $request->rating;
    $rating->comment = $request->comment;
    $rating->user_id = $user->id;

    $modelInstance->ratings()->save($rating);

    return response()->json([
        'message' => 'Rating submitted successfully.',
        'rating' => $rating
    ], 201);
}


public function edit(Request $request)
{
    $request->validate([
        'type'    => 'required|in:hotel,tour,restaurant,event_hall,playground',
        'id'      => 'required|integer',
        'rating'  => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string',
    ]);

    $user = Auth::guard('sanctum')->user();

    // Inline model resolution
    $modelClass = match ($request->type) {
        'hotel'       => \App\Models\Hotel::class,
        'tour'        => \App\Models\Tour::class,
        'restaurant'  => \App\Models\Restaurant::class,
        'event_hall'  => \App\Models\EventHall::class,
        'playground'  => \App\Models\Playground::class,
        default       => null,
    };

    if (!$modelClass) {
        return response()->json(['message' => 'Invalid rateable type.'], 400);
    }

    $rating = \App\Models\Rating::where('rateable_type', $modelClass)
        ->where('rateable_id', $request->id)
        ->where('user_id', $user->id)
        ->first();

    if (!$rating) {
        return response()->json(['message' => 'Rating not found.'], 404);
    }

    $rating->rating = $request->rating;
    $rating->comment = $request->comment;
    $rating->save();

    return response()->json(['message' => 'Rating updated successfully.', 'rating' => $rating]);

}

public function delete(Request $request)
{
   $request->validate([
        'type' => 'required|in:hotel,tour,restaurant,event_hall,playground',
        'id'   => 'required|integer',
    ]);

    $user = Auth::guard('sanctum')->user();

    // Inline model resolution
    $modelClass = match ($request->type) {
        'hotel'       => \App\Models\Hotel::class,
        'tour'        => \App\Models\Tour::class,
        'restaurant'  => \App\Models\Restaurant::class,
        'event_hall'  => \App\Models\EventHall::class,
        'playground'  => \App\Models\Playground::class,
        default       => null,
    };

    if (!$modelClass) {
        return response()->json(['message' => 'Invalid rateable type.'], 400);
    }

    $rating = \App\Models\Rating::where('rateable_type', $modelClass)
        ->where('rateable_id', $request->id)
        ->where('user_id', $user->id)
        ->first();

    if (!$rating) {
        return response()->json(['message' => 'Rating not found.'], 404);
    }

    $rating->delete();

    return response()->json(['message' => 'Rating deleted successfully.']);
}

public function rates(Request $request)
{
    $request->validate([
        'type' => 'required|in:hotel,tour,restaurant,event_hall,playground',
        'id'   => 'required|integer',
    ]);

    $modelClass = match ($request->type) {
        'hotel'       => \App\Models\Hotel::class,
        'tour'        => \App\Models\Tour::class,
        'restaurant'  => \App\Models\Restaurant::class,
        'event_hall'  => \App\Models\EventHall::class,
        'playground'  => \App\Models\Playground::class,
        default       => null,
    };

    if (!$modelClass) {
        return response()->json(['message' => 'Invalid type.'], 400);
    }

    // Ensure the place exists
    $place = $modelClass::find($request->id);
    if (!$place) {
        return response()->json(['message' => 'Place not found.'], 404);
    }

    // Get rating info
    $ratings = \App\Models\Rating::where('rateable_type', $modelClass)
        ->where('rateable_id', $request->id);

    $averageRating = round($ratings->avg('rating'), 2);
    $ratingsCount  = $ratings->count();
    $latestComments = $ratings->latest()->take(5)->get(['rating', 'comment', 'created_at']);

    return response()->json([
        'average_rating' => $averageRating ?? 0,
        'ratings_count' => $ratingsCount,
        'latest_comments' => $latestComments,
    ]);
}

public function average(Request $request)
{
    $request->validate([
        'type' => 'required|in:hotel,playground,tour,restaurant,event_hall',
        'id' => 'required|integer',
    ]);

    $modelMap = [
        'hotel' => \App\Models\Hotel::class,
        'playground' => \App\Models\Playground::class,
        'tour' => \App\Models\Tour::class,
        'restaurant' => \App\Models\Restaurant::class,
        'event_hall' => \App\Models\EventHall::class,
    ];

    $modelClass = $modelMap[$request->type] ?? null;

    if (!$modelClass) {
        return response()->json(['message' => 'Invalid type.'], 400);
    }

    $modelInstance = $modelClass::find($request->id);

    if (!$modelInstance) {
        return response()->json(['message' => 'Place not found.'], 404);
    }

    // Load ratings and calculate average
    $average = $modelInstance->ratings()->avg('rating');

    return response()->json([
        'type' => $request->type,
        'id' => $request->id,
        'average_rating' => $average ?? 0,
    ]);
}

}
