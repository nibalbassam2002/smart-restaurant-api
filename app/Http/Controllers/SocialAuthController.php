<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the social provider's authentication page.
     */
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from the social provider.
     */
    public function callback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            // يمكن هنا إعادة توجيه المستخدم إلى صفحة خطأ أو صفحة تسجيل الدخول
            // بما أننا نعمل على API، يمكننا إرجاع JSON response
            return response()->json(['message' => 'Failed to authenticate with ' . $provider, 'error' => $e->getMessage()], 400);
        }

        // البحث عن المستخدم باستخدام البريد الإلكتروني الذي تم الحصول عليه من مزود الخدمة
        $user = User::where('email', $socialUser->getEmail())->first();

        // إذا لم يكن المستخدم موجوداً، نقوم بإنشاء حساب جديد
        if (!$user) {
            $user = User::create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                'email' => $socialUser->getEmail(),
                'password' => Hash::make(Str::random(24)), // توليد كلمة مرور عشوائية للمستخدمين الذين يسجلون عبر Socialite
                'email_verified_at' => now(), // نعتبر البريد الإلكتروني موثوق به من مزود الخدمة
                'role' => 'customer', // الدور الافتراضي
                'is_active' => true,
                // يمكنك إضافة المزيد من الحقول هنا مثل social_id, social_provider إذا أردتِ تتبع ذلك
            ]);
        }

        // إنشاء توكن للمستخدم وتسجيل دخوله (API-based authentication)
        $token = $user->createToken('api-token')->plainTextToken;

        // هنا نعود بـ JSON response بدلاً من إعادة التوجيه التقليدية
        // لأننا نتعامل مع API. React App ستحتاج إلى معالجة هذا الـ token
        return response()->json([
            'message' => 'Logged in successfully with ' . $provider,
            'user' => $user,
            'token' => $token,
            'role' => $user->role,
        ]);
        //
        // === تحديث مهم لواجهة React ===
        // عادةً، في تطبيقات الـ SPA (React)، لا يمكن لـ API إرجاع redirect مباشرة
        // لـ Socialite callback لأن الـ frontend لن يعرف كيف يتعامل معها.
        // الحل الشائع هو أن:
        // 1. الـ frontend يفتح نافذة منبثقة (popup) أو يعيد توجيه المستخدم إلى: /api/auth/{provider}/redirect
        // 2. الـ callback في Laravel يعالج الدخول.
        // 3. بدلاً من إرجاع JSON، يقوم الـ Laravel API بـ إعادة توجيه (redirect) المستخدم إلى URL معين في الـ frontend
        //    مع تمرير الـ token ومعلومات المستخدم كـ query parameters (مثال: example.com/auth-callback?token=xxx&role=customer)
        // 4. الـ frontend يلتقط هذه الـ parameters ويخزنها.

        // لنقم بتعديل بسيط هنا لنتوقع أن الـ frontend سيفتح هذا الرابط مباشرة
        // ونعيد التوجيه إلى رابط في الـ frontend مع الـ token.
        // ستحتاجين إلى تعريف رابط في الـ .env لـ frontend الخاص بكِ
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000'); // رابط React App
        $redirectUrl = $frontendUrl . '/auth-callback?token=' . $token . '&user_id=' . $user->id . '&role=' . $user->role;

        return redirect($redirectUrl);
    }
}