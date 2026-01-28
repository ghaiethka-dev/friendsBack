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
    // ✅ تحويل المسار إلى رابط كامل عند جلب البيانات
   public function getImageAttribute($value)
    {
        if (!$value) {
            return null; // أو رابط لصورة افتراضية
        }
        // asset('storage/...') تولد رابطاً بناءً على APP_URL في ملف .env
        return asset('storage/' . $value);
    }
}
