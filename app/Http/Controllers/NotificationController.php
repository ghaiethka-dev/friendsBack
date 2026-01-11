<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()
                ->notifications()
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $this->authorize('create', Notification::class);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title'   => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $notification = Notification::create($validated);

        return response()->json([
            'message' => 'تم إرسال الإشعار بنجاح',
            'data'    => $notification,
        ], 201);
    }

    public function show(Notification $notification)
    {
        $this->authorize('view', $notification);

        return response()->json($notification);
    }

    public function markAsRead(Notification $notification)
    {
        $this->authorize('markAsRead', $notification);

        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'تم']);
    }

    public function destroy(Notification $notification)
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return response()->json(['message' => 'تم الحذف']);
    }
}
