<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Event::class);

        $user = $request->user();
        $query = Event::latest()->with(['user', 'worker']);

        if ($user) {
            if ($user->role === 'super_admin') {
                // السوبر أدمن يرى كل شيء
            } elseif (in_array($user->role, ['admin', 'user'])) {
                $query->where('governorate', $user->governorate);
            } elseif ($user->role === 'city_admin') {
                $query->where('city', $user->city);
            }
        } else {
            // في حال كان زائراً (Guest):
            // يمكنك هنا اختيار إظهار كل الأحداث أو أحداث معينة
            // حالياً سيظهر كل الأحداث لأننا لم نضف أي شرط where
        }
        return $query->paginate(10);
    }

    public function store(Request $request)
{
    // 1. التحقق من البيانات المرسلة فقط من الفرونت
    $request->validate([
        'title'        => 'required|string|max:255',
        'description'  => 'required|string',
        'before_image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        'after_image'  => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
    ]);

    $worker = $request->user(); // الموظف المسجل حالياً هو الـ worker

    // 2. معالجة الصور
    $beforePath = $request->file('before_image') ? $request->file('before_image')->store('events', 'public') : null;
    $afterPath  = $request->file('after_image') ? $request->file('after_image')->store('events', 'public') : null;

    // 3. إنشاء الحدث
    // أخذنا المحافظة والمدينة تلقائياً من بيانات الموظف (الـ worker) 
    // لأن الفرونت لا يوفر حقولاً لاختيارها في صفحة الإضافة
    $event = Event::create([
        'worker_id'    => $worker->id,
        'user_id'      => null, // يمكن تركه null أو ربطه بطلب سابق
        'title'        => $request->title,
        'description'  => $request->description,
        'before_image' => $beforePath,
        'after_image'  => $afterPath,
        'governorate'  => $worker->governorate, 
        'city'         => $worker->city, 
    ]);

    return response()->json([
        'message' => 'تم إضافة العمل بنجاح',
        'data'    => $event
    ], 201);
}

    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $event->update($request->only(['title','description', 'city'])); // يمكن تعديل المدينة أيضاً

        return response()->json(['message' => 'تم التحديث بنجاح']);
    }

    // App/Http/Controllers/EventController.php

public function destroy(Event $event)
{
    // حذف الصور من التخزين لتوفير المساحة
    if ($event->before_image) {
        Storage::disk('public')->delete($event->before_image);
    }
    if ($event->after_image) {
        Storage::disk('public')->delete($event->after_image);
    }

    $event->delete();

    return response()->json(['message' => 'تم حذف العمل بنجاح']);
}
}
