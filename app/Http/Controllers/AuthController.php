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
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        ]);

        $input = $request->email;
        $isEmail = filter_var($input, FILTER_VALIDATE_EMAIL);

        if ($isEmail) {
            if (User::where('email', $input)->exists()) {
                return response()->json(['message' => 'هذا البريد الإلكتروني مسجل مسبقاً'], 422);
            }
            $userData = ['email' => $input, 'phone' => null];
        } else {
            if (User::where('phone', $input)->exists()) {
                return response()->json(['message' => 'رقم الهاتف هذا مسجل مسبقاً'], 422);
            }
            $userData = ['phone' => $input, 'email' => null];
        }

        // رفع الصورة
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('profiles', 'public');
        }

        $verificationCode = rand(1000, 9999);

        // إنشاء المستخدم
        $user = User::create([
            'name'        => $request->name,
            'email'       => $userData['email'],
            'phone'       => $userData['phone'],
            'password'    => bcrypt($request->password),
            'governorate' => $request->governorate,
            'city'        => $request->city,
            'role'        => 'user',
            'verification_code' => $verificationCode,
        ]);

        // إنشاء البروفايل
        Profile::create([
            'user_id'     => $user->id,
            'name'        => $request->name,
            'email'       => $userData['email'],
            'phone'       => $userData['phone'],
            'image'       => $imagePath,
            'role'        => 'user',
            'governorate' => $request->governorate,
            'city'        => $request->city,
        ]);

        // إرسال كود التحقق للإيميل
        if ($user->email) {
            try {
                Mail::raw("كود التحقق الخاص بك هو: $verificationCode", function ($message) use ($user) {
                    $message->to($user->email)->subject('تفعيل الحساب');
                });
            } catch (\Exception $e) {
                // تسجيل الخطأ في سجلات Railway (Logs)
                Log::error("خطأ في إرسال الإيميل: " . $e->getMessage());

                // حذف المستخدم الذي تم إنشاؤه لإتاحة المحاولة مرة أخرى بعد إصلاح الإعدادات
                $user->delete();

                return response()->json([
                    'message' => 'فشل إرسال كود التحقق. يرجى التحقق من إعدادات SMTP على Railway.',
                    'error' => $e->getMessage() // سيظهر لك سبب المشكلة الحقيقي هنا
                ], 500);
            }
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'تم التسجيل، يرجى تفعيل الحساب',
            'token'   => $token,
            'user'    => $user->load('profile'),
            'require_verification' => true
        ], 201);
    }

    /////////////////////// VERIFY EMAIL
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'code'  => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }

        if ($user->verification_code == $request->code) {
            $user->email_verified_at = now();
            $user->verification_code = null;
            $user->save();

            return response()->json(['message' => 'تم تفعيل الحساب بنجاح', 'verified' => true], 200);
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