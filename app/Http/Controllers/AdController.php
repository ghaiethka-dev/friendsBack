<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Illuminate\Http\Request;

class AdController extends Controller
{
    public function index(Request $request)
{
    // الحصول على المستخدم (سيعمل الآن لأننا نقلنا الرابط في api.php)
    $user = $request->user();

    // استعلام الإعلانات النشطة
    $query = Ad::where('active', 1);

    if ($user) {
        // إذا كان سوبر أدمن، يرى كل شيء
        if ($user->role === 'super_admin') {
            // لا نطبق فلترة
        } else {
            // للمستخدم العادي والأدمن: يرى إعلانات محافظته + الإعلانات المحددة بـ "عام"
            $query->where(function ($q) use ($user) {
                $q->where('governorate', $user->governorate)
                  ->orWhere('governorate', 'عام');
            });
        }
    } else {
        // للزوار (إن وجدوا): يرى الإعلانات العامة فقط
        $query->where('governorate', 'عام');
    }

    $ads = $query->latest()->get();

    return response()->json(["ads" => $ads]);
}

    public function store(Request $request)
{
    $request->validate([
        'title'       => 'required|string',
        'description' => 'required|string',
        'governorate' => 'required|string', // المحافظة المختارة من التطبيق
        'image'       => 'nullable|image'
    ]);

    // نأخذ البيانات المرسلة من FormData في Flutter
    $data = $request->only(['title', 'description', 'governorate']);
    $data['active'] = 1; 

    if ($request->hasFile('image')) {
        $data['image'] = $request->file('image')->store('ads', 'public');
    }

    $ad = Ad::create($data);

    return response()->json([
        'message' => 'تم إنشاء الإعلان بنجاح في محافظة ' . $request->governorate,
        'ad' => $ad
    ]);
}

    public function update(Request $request, Ad $ad)
    {
        $this->authorize('update', $ad);

        $data = $request->only(['title', 'description', 'active', 'city']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('ads', 'public');
        }

        $ad->update($data);

        return response()->json([
            'message' => 'تم تحديث الإعلان بنجاح',
            'ad' => $ad
        ]);
    }

    public function destroy(Ad $ad)
    {
        $this->authorize('delete', $ad);
        $ad->delete();
        return response()->json(['message' => 'تم حذف الإعلان بنجاح']);
    }
}
