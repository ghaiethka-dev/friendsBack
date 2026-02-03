<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estate_Service extends Model
{
    protected $fillable = [
        'title',
        'description',
        'location',
    ];
    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
