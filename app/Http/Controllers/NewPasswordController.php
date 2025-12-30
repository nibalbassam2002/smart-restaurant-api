<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Traits\ApiResponseTrait;

class NewPasswordController extends Controller
{
    use ApiResponseTrait;

    /**
     * الخطوة 1: إرسال رابط الاستعادة للإيميل
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // نحاول إرسال الرابط
        // ملاحظة: لارفيل سيحاول إرسال إيميل، يجب ضبط إعدادات الإيميل لاحقاً
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return $this->successResponse(null, 'Reset link sent to your email.');
        }

        // في حال فشل الإرسال (مثلاً الإيميل غير موجود)
        return $this->errorResponse('Unable to send reset link.', 400, ['email' => __($status)]);
    }

    /**
     * الخطوة 2: تعيين كلمة المرور الجديدة
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // محاولة تغيير الباسورد باستخدام البروكر الخاص بلارفيل
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->successResponse(null, 'Password has been reset successfully.');
        }

        return $this->errorResponse('Invalid token or email.', 400, ['email' => __($status)]);
    }
}