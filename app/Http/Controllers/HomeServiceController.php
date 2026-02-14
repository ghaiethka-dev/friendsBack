<?php

namespace App\Http\Controllers;

use App\Models\Home_Service;
use App\Models\Image;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeServiceController extends Controller
{
    public function index(Request $request)
    {
        // 1. جلب بيانات الأدمن الحالي
        $currentUser = $request->user();

        // 2. تجهيز الاستعلام
        $query = Home_Service::with('images', 'user')->latest();

        // 3. تطبيق شروط الفلترة حسب الرتبة
        if ($currentUser->role === 'super_admin') {
            // السوبر أدمن: يرى كل الطلبات في العراق
        }
        elseif ($currentUser->role === 'admin') {
            // أدمن المحافظة: يرى كل الطلبات التابعة لمحافظته (بغض النظر عن المدينة)
            $query->whereHas('user', function($q) use ($currentUser) {
                $q->where('governorate', $currentUser->governorate);
            });
        }
        elseif ($currentUser->role === 'city_admin') {
            // أدمن المدينة: يرى الطلبات التي في مدينته + محافظته حصراً
            $query->whereHas('user', function($q) use ($currentUser) {
                $q->where('governorate', $currentUser->governorate) // لزيادة الأمان
                  ->where('city', $currentUser->city);
            });
        }
        elseif (!in_array($currentUser->role, ['super_admin', 'admin', 'city_admin'])) {
             $query->where('user_id', $currentUser->id);
        }
        // 4. تنفيذ الاستعلام
        $services = $query->get();

        return response()->json([
            'data' => $services
        ]);
    }

    public function show($id)
    {
        $service = Home_Service::with('images')->findOrFail($id);

        return response()->json([
            'data' => $service
        ]);
    }

    public function store(Request $request)
{
    // 1. تحديث التحقق من البيانات ليشمل الهاتف والموقع
    $request->validate([
        'service_type' => 'required|in:image_request,direct_request',
        'description'  => 'nullable|string',
        'profession'   => 'nullable|string',
        'phone'        => 'required|string',      // إجباري
        'address'      => 'nullable|string',      // اختياري
        'latitude'     => 'nullable|numeric',     // رقمي
        'longitude'    => 'nullable|numeric',     // رقمي
        'images.*'     => 'nullable|image|mimes:jpg,jpeg,png'
    ]);

    // تحقق من الطلب المباشر
    if ($request->service_type === 'direct_request' && !$request->profession) {
        return response()->json([
            'message' => 'يجب اختيار نوع الحرفة'
        ], 422);
    }

    // 2. إضافة الحقول الجديدة هنا ليتم حفظها
    $homeService = Home_Service::create([
        'user_id'      => Auth::id(),
        'service_type' => $request->service_type,
        'description'  => $request->description,
        'profession'   => $request->profession,

        // --- الإضافات الجديدة ---
        'phone'        => $request->phone,
        'address'      => $request->address,
        'latitude'     => $request->latitude,
        'longitude'    => $request->longitude,
    ]);

    // حفظ الصور (كما هو سابقاً)
    if ($request->service_type === 'image_request' && $request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $path = $image->store('home_services', 'public');

            Image::create([
                'user_id' => Auth::id(),
                'image_path' => $path,
                'home_service_id' => $homeService->id,
            ]);
        }
    }

    return response()->json([
        'message' => 'تم إنشاء الطلب بنجاح',
        'data' => $homeService->load('images')
    ], 201);
}

// في ملف HomeServiceController.php
// تأكد من إضافة هذا في الأعلى

public function updateStatus(Request $request, $id)
{
    // 1. التحقق من البيانات (نضيف admin_note لاستقبال الملاحظة من التطبيق)
    $request->validate([
        'status' => 'required|in:pending,accepted,rejected',
        'admin_note' => 'nullable|string'
    ]);

    $service = Home_Service::findOrFail($id);

    // 2. تحديث الحالة
    $service->update(['status' => $request->status]);

    // 3. إنشاء الإشعار في قاعدة البيانات فوراً
    $title = $request->status == 'accepted' ? 'تم قبول طلبك ✅' : 'تم رفض الطلب ❌';
    // إذا لم يكتب الأدمن ملاحظة، نضع نص افتراضي
    $note = $request->admin_note ?? ($request->status == 'accepted' ? 'تم قبول طلبك' : 'تم رفض طلبك');

    Notification::create([
        'user_id' => $service->user_id,
        'title'   => $title,
        'message' => $note, // هنا سيتم حفظ الموعد أو سبب الرفض
        'is_read' => false,
    ]);

    return response()->json([
        'message' => 'تم تحديث حالة الطلب وإرسال الإشعار بنجاح',
        'status' => $service->status
    ]);
}
    public function update(Request $request, $id)
    {
        $service = Home_Service::findOrFail($id);

        $request->validate([
            'description' => 'nullable|string',
            'profession'  => 'nullable|string',
            'images.*'    => 'nullable|image|mimes:jpg,jpeg,png'
        ]);

        // تحديث البيانات
        $service->update([
            'description' => $request->description ?? $service->description,
            'profession'  => $request->profession ?? $service->profession,
        ]);

        // إضافة صور جديدة (بدون حذف القديمة)
        if ($service->service_type === 'image_request' && $request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('home_services', 'public');

                Image::create([
                    'image_path' => $path,
                    'home_service_id' => $service->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'تم التحديث بنجاح',
            'data' => $service->load('images')
        ]);
    }

    // HomeServiceController.php

public function destroy($id)
{
$currentUser = Auth::user();
    $service = Home_Service::findOrFail($id);

    // 1. التحقق إذا كان المستخدم هو صاحب الطلب (للزبائن العاديين)
    if (!in_array($currentUser->role, ['super_admin', 'admin', 'city_admin'])) {

        // التأكد من الملكية
        if ($service->user_id !== $currentUser->id) {
            return response()->json(['message' => 'غير مصرح لك بحذف هذا الطلب'], 403);
        }

        // التأكد من حالة الطلب (يمكن الحذف فقط إذا كان قيد الانتظار)
        if ($service->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن إلغاء الطلب بعد قبوله أو رفضه'], 400);
        }
    }

    // 2. منع أدمن المدينة من الحذف (كما هو في كودك الأصلي)
    if ($currentUser->role === 'city_admin') {
        return response()->json(['message' => 'غير مصرح لك بحذف الطلبات'], 403);
    }

    $service->delete();

    return response()->json(['message' => 'تم إلغاء الطلب بنجاح']);
}
}
