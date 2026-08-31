<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Supplier;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'viewer']);
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'viewer']);
    }

    public function create(User $user): bool
    {
        return $user->can('manage suppliers');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('manage suppliers');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->hasRole('admin');
    }
}
