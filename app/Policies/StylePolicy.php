<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Style;

class StylePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'viewer']);
    }

    public function create(User $user): bool
    {
        return $user->can('manage styles');
    }

    public function update(User $user, Style $style): bool
    {
        return $user->can('manage styles');
    }

    public function delete(User $user, Style $style): bool
    {
        return $user->hasRole('admin');
    }
}
