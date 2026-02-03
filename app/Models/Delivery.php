<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'content',
        'location',
        'lat',
        'lon',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
