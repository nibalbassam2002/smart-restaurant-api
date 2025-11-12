<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle an incoming registration request.
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'phone_number' => ['nullable', 'string', 'max:20'], // قد تحتاجين لتعديل قواعد التحقق هذه
                // يمكنك إضافة قواعد تحقق أكثر تفصيلاً لتاريخ الميلاد
                'date_of_birth' => ['nullable', 'date'],
                'password' => ['required', 'string', 'min:8', 'confirmed'], // 'confirmed' تتطلب حقل password_confirmation
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'date_of_birth' => $request->date_of_birth,
                'password' => Hash::make($request->password),
                'role' => 'customer', // الدور الافتراضي عند التسجيل هو "customer"
                'is_active' => true, // الحسابات الجديدة تكون مفعلة تلقائياً
            ]);

            // إنشاء توكن للمستخدم بعد التسجيل الناجح
            // 'customer-token' هو اسم التوكن، يمكنك تسميته بأي اسم
            $token = $user->createToken('customer-token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token,
            ], 201); // 201 Created
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors(),
            ], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during registration.',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => [__('auth.failed')], // رسالة خطأ قياسية من Laravel
                ]);
            }

            // التحقق إذا كان الحساب غير مفعل
            if (! $user->is_active) {
                return response()->json([
                    'message' => 'Your account is inactive. Please contact support.'
                ], 403); // 403 Forbidden
            }

            // حذف أي توكنات سابقة لنفس المستخدم لضمان توكن واحد نشط لكل جهاز/جلسة
            // يمكن تعديل هذا السلوك حسب الحاجة
            $user->tokens()->delete();

            // إنشاء توكن جديد
            // 'api-token' هو اسم التوكن، يمكنك إضافة القدرات (abilities) هنا لاحقاً للتحكم بالصلاحيات
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'message' => 'Logged in successfully',
                'user' => $user,
                'token' => $token,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during login.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        // حذف التوكن الحالي الذي يستخدمه المستخدم
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}