<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile; // تأكد من استدعاء المودل
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{
    // دالة التسجيل المعدلة
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
            // إذا كان رقم هاتف، سنتجاوز التحقق عبر الإيميل حالياً (أو يمكنك تفعيل SMS)
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

        DB::beginTransaction();
        try {
            // إنشاء المستخدم
            $user = User::create([
                'name'        => $request->name,
                'email'       => $userData['email'],
                'phone'       => $userData['phone'],
                'password'    => bcrypt($request->password),
                'governorate' => $request->governorate,
                'city'        => $request->city,
                'role'        => 'user',
            ]);

            // إنشاء البروفايل
            $user->profile()->create([
                'name'        => $request->name,
                'email'       => $userData['email'],
                'phone'       => $userData['phone'],
                'image'       => $imagePath,
                'role'        => $user->role,
                'governorate' => $request->governorate,
                'city'        => $request->city,
            ]);

            // ** هنا يبدأ التعديل: إرسال كود التحقق إذا كان إيميل **
            if ($isEmail) {
                $code = rand(100000, 999999); // كود من 6 أرقام

                // تخزين الكود في قاعدة البيانات
                DB::table('verification_codes')->insert([
                    'email' => $userData['email'],
                    'code' => $code,
                    'expires_at' => Carbon::now()->addMinutes(15),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                // إرسال الإيميل (بشكل بسيط باستخدام Raw Mail للسرعة)
                // يمكنك إنشاء Mailable Class لاحقاً لتنسيق أفضل
                Mail::raw("رمز التحقق الخاص بك هو: $code", function ($message) use ($userData) {
    $message->from(config('mail.from.address'), config('mail.from.name'));
    $message->to($userData['email'])
            ->subject('رمز تأكيد الحساب');
});

                DB::commit();

                return response()->json([
                    'message' => 'تم إنشاء الحساب. يرجى التحقق من البريد الإلكتروني.',
                    'require_verification' => true,
                    'email' => $userData['email']
                ], 200);

            } else {
                // إذا كان هاتف، نعتبره مفعلاً مباشرة (حسب كودك الحالي)
                $token = $user->createToken('api_token')->plainTextToken;
                DB::commit();
                return response()->json([
                    'message' => 'تم التسجيل بنجاح',
                    'token'   => $token,
                    'user'    => $user->load('profile'),
                ], 201);
            }

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'حدث خطأ أثناء التسجيل', 'error' => $e->getMessage()], 500);
        }
    }

    // دالة جديدة: التحقق من الكود
    public function verifyEmail(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'code' => 'required|string'
    ]);

    // جلب السجل
    $record = DB::table('verification_codes')->where('email', $request->email)->first();

    if (!$record) {
        return response()->json(['message' => 'رمز التحقق منتهي أو غير موجود'], 400);
    }

    // التحقق من صحة الكود
    if ($record->code != $request->code) {
        // زيادة عدد المحاولات
        $attempts = $record->attempts + 1;

        DB::table('verification_codes')
            ->where('email', $request->email)
            ->update(['attempts' => $attempts]);

        // إذا وصلت المحاولات 5 يتم الحذف
        if ($attempts >= 5) {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                // حذف المستخدم
                $user->delete();
                // حذف كود التحقق
                DB::table('verification_codes')->where('email', $request->email)->delete();

                return response()->json([
                    'message' => 'تم حذف الحساب لتجاوز عدد المحاولات المسموح بها. الرجاء التسجيل من جديد.'
                ], 410); // كود 410 يعني Gone (لم يعد موجوداً)
            }
        }

        return response()->json([
            'message' => 'الرمز غير صحيح. متبقي ' . (5 - $attempts) . ' محاولات.',
        ], 400);
    }

    // النجاح
    $user = User::where('email', $request->email)->first();
    $user->email_verified_at = now();
    $user->save();

    // حذف الكود لأنه تم استخدامه
    DB::table('verification_codes')->where('email', $request->email)->delete();

    $token = $user->createToken('api_token')->plainTextToken;

    return response()->json([
        'message' => 'تم تفعيل الحساب بنجاح',
        'token' => $token,
        'user' => $user
    ], 200);
}

    // دالة Login (كما هي ولكن نتحقق من التفعيل)
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
        if ($field == 'email' && is_null($user->email_verified_at)) {
             return response()->json(['message' => 'يرجى تأكيد البريد الإلكتروني أولاً'], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('api_token')->plainTextToken;
if ($field == 'email' && is_null($user->email_verified_at)) {
     return response()->json(['message' => 'يرجى تأكيد البريد الإلكتروني أولاً'], 403);
 }
        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'token'   => $token,
            'user'    => $user->load('profile'),
        ]);
    }

    // Logout كما هي...
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }
}
