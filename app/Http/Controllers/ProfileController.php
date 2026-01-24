<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProfileRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function me()
    {
        $user = Auth::user();

       return response()->json([
        'id'          => $user->id,
        'name'        => $user->name,
        'email'       => $user->email,
        'phone'       => $user->phone,
        'governorate' => $user->governorate, // إرجاع القيمة من جدول users
        'city'        => $user->city,        // إرجاع القيمة من جدول users
        'image'       => optional($user->profile)->image, // من جدول البروفايل
    ]);
    }
public function update(Request $request)
{
    /** @var \App\Models\User $user */
    $user = Auth::user();

    $request->validate([
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
        'phone' => 'sometimes|string|max:20|unique:users,phone,' . $user->id,
        'password' => 'sometimes|string|min:8|confirmed',
        'city' => 'sometimes|string',
        'governorate' => 'sometimes|string',
        'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    // 1. تحديث بيانات جدول users
    if ($request->has('name')) {
        $user->name = $request->name;
    }
    if ($request->has('email')) {
        $user->email = $request->email;
    }
    if ($request->has('phone')) {
        $user->phone = $request->phone;
    }
    if ($request->has('governorate')) {
        $user->governorate = $request->governorate;
    }
    if ($request->has('city')) {
        $user->city = $request->city;
    }
    if ($request->has('password')) {
        $user->password = Hash::make($request->password);
    }

    $user->save(); // حفظ التغييرات في جدول users

    // 2. تحديث بيانات جدول profiles
    $profile = $user->profile ?? new \App\Models\Profile(['user_id' => $user->id]);

    if ($request->has('name')) {
        $profile->name = $request->name;
    }
    if ($request->has('email')) {
        $profile->email = $request->email;
    }
    if ($request->has('phone')) {
        $profile->phone = $request->phone;
    }
    if ($request->has('governorate')) {
        $profile->governorate = $request->governorate;
    }
    if ($request->has('city')) {
        $profile->city = $request->city;
    }
    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('profiles', 'public');
        $profile->image = $path;
    }

    $profile->save(); // حفظ التغييرات في جدول profiles

    return response()->json(['message' => 'تم تحديث الملف الشخصي بنجاح في الجدولين']);
}
}
