<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminManagementController extends Controller
{
    // دالة إنشاء مشرف (سواء محافظة أو مدينة)
    public function store(Request $request)
    {
        $currentUser = $request->user();
        // منع أدمن المحافظة من إنشاء حساب في محافظة غير محافظته
    if ($currentUser->role === 'admin') {
        if ($request->governorate !== $currentUser->governorate) {
            return response()->json([
                'message' => 'لا يملك أدمن المحافظة صلاحية إضافة موظفين خارج نطاق محافظته (' . $currentUser->governorate . ')'
            ], 403);
        }
        
        // تأكد أنه ينشئ city_admin فقط وليس أدمن مثله
        if ($request->role !== 'city_admin') {
             return response()->json(['message' => 'يمكنك إضافة موظفي مدن فقط'], 403);
        }
    }
        // التحقق من المدخلات
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'phone' => 'required|string|unique:users,email', // نستخدم حقل email للهاتف كما في AuthController
            'password' => 'required|min:6',
            'governorate' => 'required|string',
            'city' => 'nullable|string', // قد تكون null لمشرف المحافظة
            'role' => 'required|in:admin,city_admin', // تحديد الرتبة ضروري
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // إنشاء المستخدم
        // ملاحظة: نستخدم الهاتف في حقل email وحقل phone للحفاظ على اتساق قاعدة البيانات
        $user = User::create([
            'name' => $request->name,
            'email' => $request->phone, 
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'governorate' => $request->governorate,
            'city' => $request->city ?? 'المركز',
            'role' => $request->role,
        ]);

        // إنشاء البروفايل
        $user->profile()->create([
            'name' => $request->name,
            'email' => $request->phone,
            'phone' => $request->phone,
            'role' => $request->role,
            'governorate' => $request->governorate,
            'city' => $request->city ?? 'المركز',
        ]);

        return response()->json(['message' => 'تم إضافة المشرف بنجاح', 'user' => $user], 201);
    }

    // دالة جلب موظفي المدن (لأدمن المحافظة)
    public function getCityAdmins(Request $request)
    {
        $currentUser = $request->user();

        // جلب city_admins الذين ينتمون لنفس محافظة المستخدم الحالي
        $employees = User::where('role', 'city_admin')
                         ->where('governorate', $currentUser->governorate)
                         ->with('profile')
                         ->orderBy('created_at', 'desc')
                         ->get();

        return response()->json($employees);
    }

    // دالة جلب جميع المشرفين (للسوبر أدمن)
    public function getAllAdmins(Request $request)
    {
        // جلب كل من هو admin أو city_admin
        $employees = User::whereIn('role', ['admin', 'city_admin'])
                         ->with('profile')
                         ->orderBy('created_at', 'desc')
                         ->get();

        return response()->json($employees);
    }

    // في ملف App/Http/Controllers/AdminManagementController.php

public function destroy(Request $request, $id)
{
    $currentUser = $request->user(); // الشخص الذي يحاول الحذف
    $targetUser = User::findOrFail($id); // الشخص المراد حذفه

    // 1. إذا كان الذي يحذف هو "سوبر أدمن" -> يحذف أي شخص
    if ($currentUser->role === 'super_admin') {
        $targetUser->delete();
        return response()->json(['message' => 'تم حذف الحساب بنجاح (سوبر أدمن)']);
    }

    // 2. إذا كان الذي يحذف هو "أدمن محافظة"
    if ($currentUser->role === 'admin') {
        
        // شرط أ: لا يجوز له حذف أدمن مثله، فقط city_admin
        if ($targetUser->role !== 'city_admin') {
            return response()->json(['message' => 'لا تملك صلاحية حذف هذا المستوى الإداري'], 403);
        }

        // شرط ب: يجب أن يكون أدمن المدينة تابعاً لنفس محافظة أدمن المحافظة
        if ($targetUser->governorate !== $currentUser->governorate) {
            return response()->json(['message' => 'لا يمكنك حذف موظف خارج محافظتك'], 403);
        }

        // إذا تحققت الشروط، يتم الحذف
        $targetUser->delete();
        return response()->json(['message' => 'تم حذف موظف المدينة بنجاح']);
    }

    return response()->json(['message' => 'غير مصرح لك القيام بهذه العملية'], 403);
}
}