<?php

namespace App\Models;

use App\Models\Concerns\LogsSewingAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SewingInspectionSheet extends Model
{
    use LogsSewingAudit;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SIGNED = 'signed';

    public const STATUS_LOCKED = 'locked';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_IN_PROGRESS => 'In progress',
        self::STATUS_SIGNED => 'Signed',
        self::STATUS_LOCKED => 'Locked',
    ];

    protected $fillable = [
        'sheet_no', 'sewing_line_id', 'inspection_date', 'shift',
        'buyer_id', 'style_id', 'style_number', 'style_name', 'color', 'po_number', 'lot_number', 'bundle_no',
        'qc_id', 'status', 'current_round', 'pieces_per_audit', 'locked_at', 'created_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'locked_at' => 'datetime',
        'current_round' => 'integer',
        'pieces_per_audit' => 'integer',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(SewingLine::class, 'sewing_line_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function style(): BelongsTo
    {
        return $this->belongsTo(Style::class);
    }

    public function qc(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qc_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(SewingInspectionOperation::class)->orderBy('sequence_no');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(SewingInspectionRound::class)->orderBy('round_no');
    }

    public function results(): HasMany
    {
        return $this->hasMany(SewingInspectionResult::class);
    }

    public function defectLogs(): HasMany
    {
        return $this->hasMany(SewingDefectLog::class);
    }

    public function signoffs(): HasMany
    {
        return $this->hasMany(SewingSignoff::class);
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function isSigned(): bool
    {
        return in_array($this->status, [self::STATUS_SIGNED, self::STATUS_LOCKED], true);
    }

    public function signoff(string $role): ?SewingSignoff
    {
        return $this->signoffs->firstWhere('role', $role);
    }

    public function scopeVisibleTo($query, User $user)
    {
        $isMerchandiserOnly = $user->hasRole('merchandiser')
            && ! $user->hasAnyRole(['admin', 'manager', 'qam', 'management', 'sewing_qc', 'sewing_supervisor', 'production_manager']);

        if ($isMerchandiserOnly) {
            $query->whereIn('buyer_id', $user->assignedBuyers()->pluck('buyers.id'));
        }

        return $query;
    }
}
