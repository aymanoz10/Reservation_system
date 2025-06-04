<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email'=>'required|string|unique:admins',
            'fingerprint' => 'nullable|string|regex:/^[a-zA-Z0-9+\/=]+$/',
            'password' => 'required',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
        $admin = new Admin();
            $admin->first_name = $request->first_name;
            $admin->last_name = $request->last_name;
            $admin->email = $request->email;
            $adminname = $request->first_name . ' ' . $request->last_name;
            $admin->adminname = $adminname;
            $admin->password = $request->password;
            $admin->avatar = $request->avatar;
            $admin->fingerprint = $request->fingerprint ?? null;
            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $path = $avatar->store('avatars', 'public');
                $admin->avatar = $path;
            } else {
                $admin->avatar = null;
            }

      $admin->save();
      $token = $admin->createToken('admin Token')->plainTextToken;

      return response()->json([
        'success' => true,
        'admin' => $admin,
        'token' => $token,
    ]);
}

public function login(Request $request)
{


    $request->validate([
        'email' => 'required|string|unique:users',
        'password' => 'nullable|required_without:fingerprint',
        'fingerprint' => 'nullable|string|regex:/^[a-zA-Z0-9+\/=]+$/|required_without:password',
    ]);

    $query = Admin::where('email', $request->email);

    if ($request->filled('password')) {
        $query->where('password', $request->password);
    } elseif ($request->filled('fingerprint')) {
        $query->where('fingerprint', $request->fingerprint);
    }

    $admin = $query->first();

    if ($admin) {
        $admin->avatar = asset($admin->avatar);
        return response()->json([
            'success' => true,
            'token' => $admin->createToken('admin Token')->plainTextToken,
            'user' => $admin,
        ]);
    }
    return response()->json([
        'success' => false,
        'message' => 'wrong information',
    ], 200);
}


    public function logout(Request $request)
    {
        auth()->guard('admin')->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Successfully logged out']);
    }

    public function get(Request $request)
    {
        $admin = auth('admin')->user();

        if (!$admin) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $imageUrl = $admin->avatar ? asset(Storage::url($admin->avatar)) : null;

        return response()->json([
            'image' => $imageUrl,
            'admin' => $admin,
        ], 200);
    }
   




}
