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
     * توجيه المستخدم لصفحة جوجل/فيسبوك
     */
    public function redirect($provider)
    {
        // نستخدم stateless() لأننا API ولا نستخدم Sessions
        return Socialite::driver($provider)->stateless()->redirect();
    }

    /**
     * استقبال المستخدم بعد العودة
     */
    public function callback($provider)
    {
        // تحديد رابط الفرونت إند (الرياكت)
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

        try {
            // جلب البيانات من جوجل/فيسبوك (Stateless مهمة هنا)
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            // في حال الفشل، نرجعه لصفحة اللوجن في الرياكت مع رسالة خطأ
            return redirect($frontendUrl . '/login?error=social_auth_failed');
        }

        // البحث عن المستخدم أو إنشاؤه
        $user = User::firstOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                'password' => Hash::make(Str::random(24)), // باسورد عشوائي
                'email_verified_at' => now(),
                'role' => 'customer',
                'is_active' => true,
            ]
        );

        // إنشاء التوكن
        $token = $user->createToken('social-token')->plainTextToken;

        // === الخطوة الأهم: التوجيه للفرونت إند ===
        // نوجه المستخدم لصفحة خاصة في الرياكت ونرسل التوكن في الرابط
        // الرياكت سيقرأ التوكن من الرابط ويخزنه ويسجل الدخول
        
        $redirectUrl = "{$frontendUrl}/auth/callback?token={$token}&name={$user->name}&role={$user->role}";

        return redirect($redirectUrl);
    }
}