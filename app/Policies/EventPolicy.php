<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function before(User $user)
    {
        if ($user->role === 'super_admin') {
            return true;
        }
        return null;
    }


    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(User $user, Event $event): bool
    {

        if (in_array($user->role, ['admin', 'user', 'city_admin'])) {
            return $user->governorate === $event->governorate;
        }

        return false;
    }


    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }


    public function update(User $user, Event $event): bool
    {
        return $user->role === 'admin'
            && $user->id === $event->worker_id;
    }


    public function delete(User $user, Event $event): bool
    {
        return $user->role === 'admin'
            && $user->id === $event->worker_id;
    }
}
