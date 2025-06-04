<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class GoogleAuthController extends Controller
{
    /**
     * تسجيل الدخول باستخدام توكين Google.
     */
    public function handleGoogleLogin(Request $request)
    {
        $googleToken = $request->token;

        try {
            // جلب بيانات المستخدم من Google باستخدام التوكين
            $googleUser = Socialite::driver('google')->userFromToken($googleToken);

            // البحث عن المستخدم في قاعدة البيانات
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // التأكد من أن الطلب يحتوي على كلمة مرور
                if (!$request->has('password')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'يجب إدخال كلمة مرور لإنشاء الحساب.',
                    ], 400);
                }

                // إنشاء مستخدم جديد
                $user = new User();
                $user->first_name = $googleUser->getName();
                $user->email = $googleUser->getEmail();
                $user->username = $googleUser->getName();
                $user->avatar = $googleUser->getAvatar();
                $user->password = Hash::make($request->password); // تشفير كلمة المرور

                $user->save(); // حفظ المستخدم في قاعدة البيانات
            }

            // إنشاء توكين جديد للمستخدم عبر Sanctum أو Passport
            $token = $user->createToken('Google Login Token')->plainTextToken;

            return response()->json([
                'success' => true,
                'user' => $user,
                'token' => $token,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في التحقق من التوكين عبر Google.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
