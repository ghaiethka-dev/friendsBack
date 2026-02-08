<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Home_Service extends Model
{
protected $table = 'home_services';

    protected $fillable = [
        'user_id',
        'description',
        'service_type',
        'profession',
        'phone',
        'address',
        'latitude',
        'longitude'
        'status',
    ];
    public function images()
    {
        return $this->hasMany(Image::class, 'home_service_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
