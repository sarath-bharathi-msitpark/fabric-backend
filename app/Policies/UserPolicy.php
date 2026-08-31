<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->can('manage users');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('manage users');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can('manage users') && $user->id !== $target->id;
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->can('manage users');
    }

    public function deactivate(User $user, User $target): bool
    {
        return $user->can('manage users') && $user->id !== $target->id;
    }
}
