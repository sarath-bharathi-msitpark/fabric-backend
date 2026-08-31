<?php

namespace App\Policies;

use App\Models\User;
use App\Models\FabricRecord;

class FabricRecordPolicy
{
    public function view(User $user, FabricRecord $record): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'viewer']);
    }

    public function create(User $user): bool
    {
        return $user->can('upload data');
    }

    public function update(User $user, FabricRecord $record): bool
    {
        return $user->can('edit records');
    }

    public function delete(User $user, FabricRecord $record): bool
    {
        return $user->can('delete records');
    }

    public function export(User $user): bool
    {
        return $user->can('export reports');
    }
}
