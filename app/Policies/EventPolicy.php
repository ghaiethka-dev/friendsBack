<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function before(User $user)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return null;
    }
    /**
     * عرض كل الأحداث
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * عرض حدث واحد
     */
    public function view(?User $user, Event $event): bool
    {
        return true;
    }

    /**
     * إنشاء حدث
     */
    public function create(User $user): bool
    {
        return $user->role === 'city_admin';
    }

    /**
     * تعديل حدث
     */
    public function update(User $user, Event $event): bool
    {
        return $user->role === 'city_admin'
            && $user->id === $event->worker_id;
    }

    /**
     * حذف حدث
     */
    public function delete(User $user, Event $event): bool
    {
        return ($user->role === 'city_admin' && $user->id === $event->worker_id)
            || $user->role === 'admin';
    }

    /**
     * الاستعادة (لو استخدمت soft delete)
     */
    public function restore(User $user, Event $event): bool
    {
        return in_array($user->role, ['admin', 'super_admin']);
    }

    /**
     * الحذف النهائي
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return $user->role === 'super_admin';
    }
}
