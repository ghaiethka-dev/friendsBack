<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * عرض جميع الإشعارات (مع إمكانية التصفية والترتيب)
     */
    public function index(Request $request)
    {
        $query = Notification::where('user_id', Auth::id())->with('user');

        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        $orderBy = $request->get('order_by', 'created_at');
        $orderDirection = $request->get('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        $perPage = $request->get('per_page', 15);
        $notifications = $query->paginate($perPage);

        return NotificationResource::collection($notifications);
    }

    /**
     * تحديد إشعار كمقروء
     */
    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        if ($notification->is_read) {
            return response()->json(['message' => 'الإشعار مقروء بالفعل'], 400);
        }

        $notification->update(['is_read' => true]);

        return new NotificationResource($notification);
    }

    /**
     * تحديد كل إشعارات المستخدم كمقروءة
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'تم تحديد جميع الإشعارات كمقروءة'], 200);
    }

    /**
     * الحصول على إشعارات المستخدم الحالي
     */
    public function getUserNotifications(Request $request)
    {
        $request->validate([
            'is_read' => 'sometimes|boolean'
        ]);

        $query = Notification::where('user_id', Auth::id());

        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        $notifications = $query->latest()->get();

        return NotificationResource::collection($notifications);
    }

    /**
     * إرسال إشعار للجميع (للاستخدام من قبل الأدمن فقط)
     */
    public function sendToAll(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:1000|min:5',
        ]);

        $users = \App\Models\User::all();

        foreach ($users as $user) {
            $user->notifications()->create([
                'content' => $request->content,
            ]);
        }

        return response()->json(['message' => 'تم إرسال الإشعار لجميع المستخدمين']);
    }
}
