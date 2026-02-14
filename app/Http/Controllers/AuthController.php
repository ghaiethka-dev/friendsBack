<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log; // تأكد من وجود هذا الاستدعاء
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{
    /////////////////////// REGISTER
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string',
            'password' => 'required|min:6|confirmed',
            'governorate' => 'required|string',
            'city' => 'required|string',
        ]);

        $input = $request->email;
        $isEmail = filter_var($input, FILTER_VALIDATE_EMAIL);

        // التحقق من التكرار
        if ($isEmail) {
            if (User::where('email', $input)->exists()) {
                return response()->json(['message' => 'البريد الإلكتروني مسجل مسبقاً'], 422);
            }
        } else {
             return response()->json(['message' => 'يرجى التسجيل باستخدام بريد إلكتروني صالح'], 422);
        }

        // 1. إنشاء المستخدم
        $user = User::create([
            'name'        => $request->name,
            'email'       => $input,
            'password'    => bcrypt($request->password),
            'governorate' => $request->governorate,
            'city'        => $request->city,
            // لا تقم بتسجيل الدخول مباشرة هنا
        ]);

        // 2. توليد الكود وإرساله
        $code = rand(1000, 9999);
        
        // حفظ الكود في جدول verification_codes الذي رفعته أنت
        DB::table('verification_codes')->updateOrInsert(
            ['email' => $user->email],
            [
                'code' => $code,
                'created_at' => now(),
                'updated_at' => now(),
                'expires_at' => now()->addMinutes(15) // افترضنا وجود هذا العمود، إذا لم يكن موجوداً احذف السطر
            ]
        );

        // محاولة الإرسال
        try {
            Mail::raw("رمز التحقق الخاص بك هو: $code", function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('تفعيل حساب CloseFriend');
            });
        } catch (\Exception $e) {
            // حتى لو فشل الإرسال، سنخبر المستخدم
            return response()->json([
                'message' => 'تم إنشاء الحساب ولكن فشل إرسال الرمز. تواصل مع الدعم.',
                'error' => $e->getMessage()
            ], 500);
        }

        // إرجاع رد يطلب من فلاتر الانتقال لصفحة التحقق
        return response()->json([
            'message' => 'تم إنشاء الحساب، يرجى تفعيل الإيميل',
            'require_verification' => true,
            'email' => $user->email
        ], 201);
    }

    // دالة التحقق من الكود (API)
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required'
        ]);

        // البحث في جدول الأكواد
        $record = DB::table('verification_codes')
                    ->where('email', $request->email)
                    ->where('code', $request->code)
                    ->first();

        if ($record) {
            // تفعيل المستخدم
            $user = User::where('email', $request->email)->first();
            $user->email_verified_at = now();
            $user->save();

            // حذف الكود المستخدم
            DB::table('verification_codes')->where('email', $request->email)->delete();

            // إنشاء توكن للدخول
            $token = $user->createToken('api_token')->plainTextToken;

            return response()->json([
                'message' => 'تم التفعيل بنجاح',
                'token' => $token,
                'user' => $user
            ], 200);
        } else {
            return response()->json(['message' => 'كود التحقق غير صحيح'], 400);
        }
    }

    /////////////////////// LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required',
            'password' => 'required',
        ]);

        $loginInput = $request->email;
        $field = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($field, $loginInput)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'البيانات غير صحيحة'], 401);
        }

        // التحقق من تفعيل الإيميل
        if ($field == 'email' && is_null($user->email_verified_at)) {
             return response()->json(['message' => 'يرجى تأكيد البريد الإلكتروني أولاً'], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'token'   => $token,
            'user'    => $user->load('profile'),
        ]);
    }

    /////////////////////// LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج']);
    }
}