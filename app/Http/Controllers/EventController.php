<?php

namespace App\Http\Controllers;

use App\Models\Event;

use Illuminate\Http\Request;

class EventController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('viewAny', Event::class);

        $user = $request->user();
        $query = Event::latest()->with(['user', 'worker']);

        if ($user && !in_array($user->role, ['admin', 'super_admin'])) {
            $query->where('governorate', $user->governorate);
        }

        return $query->paginate(10);
    }

    public function show(Event $event)
    {
        $this->authorize('view', $event);

        return $event->load(['user', 'worker']);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Event::class);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'description' => 'required|string',
            'before_image' => 'nullable|image',
            'after_image' => 'nullable|image',
        ]);

        $worker = $request->user(); // العامل المسجل دخول
        if (!$worker) {
            return response()->json(['message' => 'يجب تسجيل الدخول أولاً'], 401);
        }

        $client = \App\Models\User::findOrFail($request->user_id);

        $before = $request->file('before_image')?->store('events', 'public');
        $after  = $request->file('after_image')?->store('events', 'public');

        $event = Event::create([
            'user_id'     => $client->id,
            'worker_id'   => $worker->id, // الآن مضمون أن ليس null
            'description' => $request->description,
            'before_image' => $before,
            'after_image' => $after,
            'governorate' => $client->governorate,
        ]);

        return response()->json([
            'message' => 'تم إنشاء الحدث بنجاح',
            'event' => $event
        ], 201);
    }


    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $event->update($request->only(['description']));

        return response()->json(['message' => 'تم التحديث بنجاح']);
    }

    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

        return response()->json(['message' => 'تم الحذف']);
    }
}
