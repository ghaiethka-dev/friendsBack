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
        'phone',
        'password',
        'role',
        'governorate',
        'city',
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

    public function eventsAsUser()
    {
        return $this->hasMany(Event::class, 'user_id');
    }

    public function eventsAsWorker()
    {
        return $this->hasMany(Event::class, 'worker_id');
    }


    public function isUser()
    {
        return $this->role === 'user';
    }

    public function isAdmin()
    {
        return in_array($this->role, ['admin', 'superadmin']);
    }

    public function isSuperAdmin()
    {
        return $this->role === 'superadmin';
    }
}
