<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'image',
        'user_id',
        'governorate', // ✅ أضف هذا
        'city',        // ✅ أضف هذا
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
