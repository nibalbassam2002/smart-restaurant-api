<?php

namespace App\Traits;

/*
|--------------------------------------------------------------------------
| API Response Trait
|--------------------------------------------------------------------------
|
| هذا الملف وظيفته توحيد شكل الردود في كل المشروع
| بدلاً من كتابة شكل الـ JSON يدوياً في كل مرة
|
*/

trait ApiResponseTrait
{
    /**
     * دالة الرد الناجح (Success)
     * 
     * @param mixed $data    البيانات التي نريد إرسالها (مثل بيانات المستخدم)
     * @param string $message رسالة النجاح (مثل: تم التسجيل بنجاح)
     * @param int $code      كود الحالة (افتراضياً 200)
     */
    public function successResponse($data = null, $message = 'Operation successful', $code = 200)
    {
        return response()->json([
            'status' => true,      // دائماً true في النجاح
            'message' => $message, // الرسالة
            'data' => $data,       // البيانات
        ], $code);
    }

    /**
     * دالة رد الخطأ (Error)
     * 
     * @param string $message رسالة الخطأ (مثل: كلمة المرور غير صحيحة)
     * @param int $code      كود الخطأ (مثل 404 أو 401)
     * @param mixed $errors  تفاصيل الأخطاء (اختياري، يستخدم مع Validation)
     */
    public function errorResponse($message = 'Something went wrong', $code = 400, $errors = null)
    {
        $response = [
            'status' => false,     // دائماً false في الخطأ
            'message' => $message, // الرسالة
        ];

        // نضيف الأخطاء التفصيلية فقط إذا كانت موجودة (عشان ما نرسل null)
        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}