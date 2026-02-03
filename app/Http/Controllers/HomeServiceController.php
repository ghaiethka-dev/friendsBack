<?php

namespace App\Http\Controllers;

use App\Models\Home_Service;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeServiceController extends Controller
{
    public function index()
    {
        $services = Home_Service::with('images', 'user')->latest()->get();

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

    public function destroy($id)
    {
        $service = Home_Service::findOrFail($id);
        $service->delete(); // الصور تُحذف تلقائيًا (cascade)

        return response()->json([
            'message' => 'تم حذف الطلب بنجاح'
        ]);
    }
}
