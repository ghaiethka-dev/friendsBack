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
            'phone' => 'sometimes|string|max:20',
            'password' => 'sometimes|string|min:8|confirmed',
            'city' => 'sometimes|string',
    'governorate' => 'sometimes|string',
    'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        $profile = $user->profile;
        if ($request->has('city')) {
        $profile->city = $request->city;
    }
    
    if ($request->hasFile('image')) {
        // كود رفع الصورة وتحديث المسار
        $path = $request->file('image')->store('profiles', 'public');
        $profile->image = $path;
    }

        $user->save();

        if ($request->has('phone')) {
            $profile = $user->profile ?? new Profile();
            $profile->phone = $request->phone;
            $profile->user_id = $user->id;
            $profile->save();
        }

        return response()->json(['message' => 'Profile updated successfully']);
    }
}
