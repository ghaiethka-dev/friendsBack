<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Illuminate\Http\Request;

class AdController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Ad::where('active', 1)->latest();

        if (!$user) {
            return response()->json(["ads" => $query->get()]);
        }

        if ($user->role === 'super_admin') {
        } 
        elseif (in_array($user->role, ['admin', 'user'])) {
            $query->where('governorate', $user->governorate);
        } 
        elseif ($user->role === 'city_admin') {
            $query->where('city', $user->city);
        }

        return response()->json(["ads" => $query->get()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Ad::class);

        $request->validate([
            'title'       => 'required|string',
            'description' => 'required|string',
            'city'        => 'required|string', 
            'image'       => 'nullable|image'
        ]);

        $user = $request->user();

        $data = $request->only(['title', 'description', 'city']);
        $data['active'] = $request->active ? 1 : 0;
        
        $data['governorate'] = $user->governorate;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('ads', 'public');
        }   

        $ad = Ad::create($data);

        return response()->json([
            'message' => 'تم إنشاء الإعلان بنجاح في محافظة ' . $user->governorate,
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