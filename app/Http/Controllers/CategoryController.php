<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class CategoryController extends Controller
{
    public function index(Request $request)
{
    $query = Category::query();

    // Apply search if provided
    if ($request->has('search') && !empty($request->search)) {
        $searchTerm = $request->search;
        $query->where(function ($q) use ($searchTerm) {
            $q->where('ar_title', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('en_title', 'LIKE', '%' . $searchTerm . '%');
        });
    }

    $categories = $query->get();

    // Optional: Return a message if nothing was found
    if ($categories->isEmpty()) {
        return response()->json(['error' => 'No categories found'], 404);
    }

    return CategoryResource::collection($categories);
}


public function create(Request $request)
{
    // Check if admin is authenticated
    if (!Auth::guard('admin')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Validate request data, including image as file
    $validated = $request->validate([
        'ar_title' => 'required|string|max:255',
        'en_title' => 'required|string|max:255',
        'image'    => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // max 2MB
    ]);

    // Store the image file and get path
    $path = $request->file('image')->store('categories', 'public');

    // Create the category with image path
    $category = Category::create([
        'ar_title' => $validated['ar_title'],
        'en_title' => $validated['en_title'],
        'image'    => $path, // saved path like "categories/filename.jpg"
    ]);

    return response()->json([
        'message' => 'Category created successfully.',
        'category' => $category,
    ], 201);
}

}