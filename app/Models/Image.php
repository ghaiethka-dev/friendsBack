<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = [
        'image_path',
        'home_service_id',
        'estate_service_id',
    ];
    public function homeService()
    {
        return $this->belongsTo(Home_Service::class);
    }
    public function estateService()
    {
        return $this->belongsTo(Estate_Service::class);
    }
}
