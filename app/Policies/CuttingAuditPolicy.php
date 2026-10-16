<?php

namespace App\Policies;

use App\Models\CuttingAudit;
use App\Models\User;

class CuttingAuditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view cutting register')
            || $user->hasAnyRole(['admin', 'manager', 'cutting_qc', 'cutting_incharge', 'qam', 'management', 'merchandiser']);
    }

    public function view(User $user, CuttingAudit $audit): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($this->isMerchandiserOnly($user)) {
            return $audit->buyer_id !== null
                && $user->assignedBuyers()->whereKey($audit->buyer_id)->exists();
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create cutting audit');
    }

    public function update(User $user, CuttingAudit $audit): bool
    {
        return $user->can('edit cutting header');
    }

    public function delete(User $user, CuttingAudit $audit): bool
    {
        return $user->hasRole('admin');
    }

    public function export(User $user): bool
    {
        return $user->can('export cutting reports');
    }

    public function submitStage(User $user): bool
    {
        return $user->can('submit cutting stage');
    }

    public function signoff(User $user, string $stage): bool
    {
        return $user->can('signoff '.$stage) || $user->hasRole('admin');
    }

    public function specialApprove(User $user): bool
    {
        return $user->can('approve special approval');
    }

    public function release(User $user): bool
    {
        return $user->can('release bundles');
    }

    public function manageChecklists(User $user): bool
    {
        return $user->can('manage cutting checklists');
    }

    protected function isMerchandiserOnly(User $user): bool
    {
        return $user->hasRole('merchandiser')
            && ! $user->hasAnyRole(['admin', 'manager', 'qam', 'management', 'cutting_qc', 'cutting_incharge']);
    }
}
