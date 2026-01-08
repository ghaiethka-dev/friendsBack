<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Home_Service extends Model
{
    protected $fillable = [
        'title',
        'description',
        'location',
    ];
}
