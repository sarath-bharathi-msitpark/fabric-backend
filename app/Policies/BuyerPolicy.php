<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Buyer;

class BuyerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'viewer']);
    }

    public function create(User $user): bool
    {
        return $user->can('manage buyers');
    }

    public function update(User $user, Buyer $buyer): bool
    {
        return $user->can('manage buyers');
    }

    public function delete(User $user, Buyer $buyer): bool
    {
        return $user->hasRole('admin');
    }
}
