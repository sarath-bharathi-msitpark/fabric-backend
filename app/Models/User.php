<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function uploadedRecords()
    {
        return $this->hasMany(FabricRecord::class, 'uploaded_by');
    }

    public function inspections()
    {
        return $this->hasMany(InspectionDetail::class, 'inspected_by');
    }

    public function resolvedAlerts()
    {
        return $this->hasMany(Alert::class, 'resolved_by');
    }

    public function cuttingAudits()
    {
        return $this->hasMany(CuttingAudit::class, 'created_by');
    }

    public function assignedBuyers()
    {
        return $this->belongsToMany(Buyer::class);
    }

    public function cuttingSignoffs()
    {
        return $this->hasMany(CuttingSignoff::class);
    }

    /**
     * The route name this user should land on after login / when denied access.
     */
    public function homeRoute(): string
    {
        if ($this->hasAnyRole(['admin', 'manager'])) {
            return 'dashboard';
        }

        if ($this->hasAnyRole(['cutting_qc', 'cutting_incharge', 'qam', 'management', 'merchandiser'])) {
            return 'admin.cutting.dashboard';
        }

        return 'dashboard';
    }
}
