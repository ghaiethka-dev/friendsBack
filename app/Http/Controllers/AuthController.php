<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /////////////////////// REGISTER
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string', // هذا الحقل هو المدخل من المستخدم (إيميل أو هاتف)
            'password' => 'required|min:6|confirmed',
            'governorate' => 'required|string',
            'city' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        ]);

        $input = $request->email;
        // تحديد هل المدخل إيميل أم هاتف
        $isEmail = filter_var($input, FILTER_VALIDATE_EMAIL);

        // التحقق من عدم التكرار يدوياً قبل الإنشاء
        if ($isEmail) {
            if (\App\Models\User::where('email', $input)->exists()) {
                return response()->json(['message' => 'هذا البريد الإلكتروني مسجل مسبقاً'], 422);
            }
            $userData = ['email' => $input, 'phone' => null];
        } else {
            if (\App\Models\User::where('phone', $input)->exists()) {
                return response()->json(['message' => 'رقم الهاتف هذا مسجل مسبقاً'], 422);
            }
            $userData = ['phone' => $input, 'email' => null];
        }

        // رفع الصورة
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('profiles', 'public');
        }

        // إنشاء المستخدم مع البيانات المحددة (userData)
        // ... داخل دالة register بعد رفع الصورة

        // إنشاء المستخدم (جدول users)
        $user = \App\Models\User::create([
            'name'        => $request->name,
            'email'       => $userData['email'],
            'phone'       => $userData['phone'],
            'password'    => bcrypt($request->password),
            'governorate' => $request->governorate,
            'city'        => $request->city,
            'role'        => 'user', // قم بتحديد الرتبة هنا يدوياً أو اجعلها افتراضية في قاعدة البيانات
        ]);

        // إنشاء البروفايل (جدول profiles)
        $user->profile()->create([
            'name'        => $request->name,
            'email'       => $userData['email'],
            'phone'       => $userData['phone'],
            'image'       => $imagePath,
            'role'        => $user->role, // خذ القيمة من المستخدم الذي أنشأته للتو
            'governorate' => $request->governorate,
            'city'        => $request->city,
        ]);
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'تم التسجيل بنجاح',
            'token'   => $token,
            'user'    => $user->load('profile'),
        ], 201);
    }

    /////////////////////// LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required', // الاسم في البوست مان email، لكن القيمة قد تكون هاتف
            'password' => 'required',
        ]);

        $loginInput = $request->email;

        // تحديد نوع المدخل للبحث في العمود الصحيح
        $field = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        // البحث عن المستخدم بناءً على الحقل المحدد
        $user = User::where($field, $loginInput)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'البيانات غير صحيحة'
            ], 401);
        }

        // حذف التوكنات القديمة
        $user->tokens()->delete();

        // إنشاء توكن جديد
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'token'   => $token,
            'user'    => $user->load('profile'), // إرجاع بيانات المستخدم مع البروفايل
        ]);
    }

    /////////////////////// LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح'
        ]);
    }
}
