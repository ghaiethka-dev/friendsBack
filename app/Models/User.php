<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function homeServices()
    {
        return $this->hasMany(Home_Service::class);
    }
    public function estateServices()
    {
        return $this->hasMany(Estate_Service::class);
    }
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
