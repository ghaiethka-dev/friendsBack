<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail; // استيراد Mail
use App\Mail\VerificationCodeMail; // استيراد الكلاس الذي أنشأناه

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
             // إذا كان رقم هاتف، يمكنك تخطي التحقق بالإيميل أو استخدام SMS (خارج نطاق السؤال الحالي)
            if (User::where('phone', $input)->exists()) {
                return response()->json(['message' => 'رقم الهاتف هذا مسجل مسبقاً'], 422);
            }
            $userData = ['phone' => $input, 'email' => null];
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('profiles', 'public');
        }

        // توليد كود عشوائي من 4 أرقام
        $verificationCode = rand(1000, 9999);

        $user = User::create([
            'name'        => $request->name,
            'email'       => $userData['email'],
            'phone'       => $userData['phone'],
            'password'    => bcrypt($request->password),
            'governorate' => $request->governorate,
            'city'        => $request->city,
            'role'        => 'user',
            'verification_code' => $isEmail ? $verificationCode : null, // حفظ الكود فقط إذا كان إيميل
             // لاحظ أننا لم نقم بإنشاء email_verified_at هنا، سيبقى null
        ]);

        $user->profile()->create([
            'name'        => $request->name,
            'email'       => $userData['email'],
            'phone'       => $userData['phone'],
            'image'       => $imagePath,
            'role'        => $user->role,
            'governorate' => $request->governorate,
            'city'        => $request->city,
        ]);

        // إرسال الإيميل إذا كان المسجل يستخدم بريد إلكتروني
        if ($isEmail) {
            try {
                Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));
            } catch (\Exception $e) {
                // يمكن تجاهل الخطأ أو إرجاعه، لكن المستخدم تم إنشاؤه
            }

            return response()->json([
                'message' => 'يرجى التحقق من البريد الإلكتروني',
                'require_verification' => true,
                'email' => $user->email
            ], 200);
        }

        // إذا كان هاتف (بدون تحقق إيميل حسب طلبك الحالي) نعطيه التوكن مباشرة
        $token = $user->createToken('api_token')->plainTextToken;
        return response()->json([
            'message' => 'تم التسجيل بنجاح',
            'token'   => $token,
            'user'    => $user->load('profile'),
        ], 201);
    }

    /////////////////////// Verify OTP Function (NEW)
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }

        if ($user->verification_code == $request->code) {
            $user->email_verified_at = now();
            $user->verification_code = null; // حذف الكود بعد الاستخدام
            $user->save();

            $token = $user->createToken('api_token')->plainTextToken;

            return response()->json([
                'message' => 'تم تفعيل الحساب بنجاح',
                'token'   => $token,
                'user'    => $user->load('profile'),
            ], 200);
        } else {
            return response()->json(['message' => 'رمز التحقق غير صحيح'], 400);
        }
    }

    /////////////////////// LOGIN (Modified)
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

        // منع الدخول إذا لم يكن مفعل الإيميل
        if ($field === 'email' && $user->email_verified_at === null) {
            return response()->json([
                'message' => 'يرجى تفعيل البريد الإلكتروني أولاً',
                'require_verification' => true // إشارة للتطبيق لفتح شاشة الكود
            ], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'token'   => $token,
            'user'    => $user->load('profile'),
        ]);
    }

    // ... (دالة logout تبقى كما هي)


    /////////////////////// LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح'
        ]);
    }
}
