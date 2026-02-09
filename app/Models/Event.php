<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'user_id',
        'worker_id',
        'title',
        'description',
        'before_image',
        'after_image',
        'governorate',
        'city',
    ];
    public function getBeforeImageUrlAttribute()
    {
        return $this->before_image ? asset('storage/' . $this->before_image) : null;
    }

    // تحويل رابط الصورة "بعد" إلى رابط كامل
    public function getAfterImageUrlAttribute()
    {
        return $this->after_image ? asset('storage/' . $this->after_image) : null;
    }

    // إضافة الروابط تلقائياً عند جلب البيانات
    protected $appends = ['before_image_url', 'after_image_url'];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

}
