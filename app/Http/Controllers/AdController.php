<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Illuminate\Http\Request;

class AdController extends Controller
{
    public function index()
    {
        $ads=Ad::Where('active',1)->latest()->get();
        return response()->json(["ads" => $ads]);
    }
    public function store(Request $request)
    {
    $this->authorize('create', Ad::class);

        $data = [];

        $data['title'] = $request->title;
        $data['description'] = $request->description;
        $data['active'] = $request->active ? 1 : 0;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('ads', 'public');
        }   

        $ad = Ad::create($data);

        return response()->json([
            'message' => 'تم إنشاء الإعلان بنجاح',
            'ad' => $ad
        ]);
    }


    public function update(Request $request, Ad $ad)
    {
        $this->authorize('update', $ad);

        $data = [];

        $data['title'] = $request->title;
        $data['description'] = $request->description;
        $data['active'] = $request->active ? 1 : 0;

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

        return response()->json([
            'message' => 'تم حذف الإعلان بنجاح'
        ]);
    }
        
}
