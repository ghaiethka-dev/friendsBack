<?php

namespace App\Policies;

use App\Models\Ad;
use App\Models\User;

class AdPolicy
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

    public function view(User $user, Ad $ad): bool
    {
        if (in_array($user->role, ['admin', 'user', 'city_admin'])) {
            return $user->governorate === $ad->governorate;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Ad $ad): bool
    {
        return $user->role === 'admin' && $user->governorate === $ad->governorate;
    }

    public function delete(User $user, Ad $ad): bool
    {
        return $user->role === 'admin' && $user->governorate === $ad->governorate;
    }
}