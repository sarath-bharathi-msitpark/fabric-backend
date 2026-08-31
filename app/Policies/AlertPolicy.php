<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Alert;

class AlertPolicy
{
    public function resolve(User $user, Alert $alert): bool
    {
        return $user->can('resolve alerts');
    }
}
