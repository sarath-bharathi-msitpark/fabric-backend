<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuttingAudit extends Model
{
    public const STAGES = ['lay', 'cut_panel', 'bundling'];

    public const STAGE_LABELS = [
        'lay' => 'Lay Inspection',
        'cut_panel' => 'Cut Panel Inspection',
        'bundling' => 'Bundling Inspection',
    ];

    public const RESULTS = [
        'lay' => [
            'approved' => 'Approved to CUT',
            'not_approved' => 'Not approved to CUT',
            'special_approval' => 'Cut on special approval',
        ],
        'cut_panel' => [
            'approved' => 'Approved for bundling',
            'not_approved' => 'Not approved for bundling',
            'special_approval' => 'On special approval',
        ],
        'bundling' => [
            'approved' => 'Approved to issue for sewing',
            'not_approved' => 'Not approved to issue',
            'special_approval' => 'On special approval',
        ],
    ];

    protected $fillable = [
        'audit_no', 'cutting_table_id', 'audit_date', 'shift',
        'buyer_id', 'style_number', 'style_name', 'po_number', 'lot_number',
        'fabric', 'colour', 'dia',
        'lay_number', 'cut_number', 'number_of_sizes', 'lay_method', 'marking_type',
        'required_lay_length', 'required_lay_width', 'actual_lay_length', 'actual_lay_width',
        'required_plies', 'actual_plies', 'actual_lay_height', 'total_parts', 'tolerance_pct',
        'status', 'current_stage', 'revision', 'parent_id', 'root_id', 'created_by', 'closed_at',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'closed_at' => 'datetime',
        'required_lay_length' => 'decimal:2',
        'required_lay_width' => 'decimal:2',
        'actual_lay_length' => 'decimal:2',
        'actual_lay_width' => 'decimal:2',
        'actual_lay_height' => 'decimal:2',
        'tolerance_pct' => 'decimal:2',
        'number_of_sizes' => 'integer',
        'required_plies' => 'integer',
        'actual_plies' => 'integer',
        'total_parts' => 'integer',
        'revision' => 'integer',
    ];

    public function cuttingTable(): BelongsTo
    {
        return $this->belongsTo(CuttingTable::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'root_id')->orderBy('revision');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(CuttingStage::class);
    }

    public function sizePatterns(): HasMany
    {
        return $this->hasMany(CuttingSizePattern::class)->orderBy('sort_order');
    }

    public function bundles(): HasMany
    {
        return $this->hasMany(CuttingBundle::class);
    }

    public function signoffs(): HasMany
    {
        return $this->hasMany(CuttingSignoff::class);
    }

    public function specialApprovals(): HasMany
    {
        return $this->hasMany(CuttingSpecialApproval::class);
    }

    public function stage(string $stage): ?CuttingStage
    {
        return $this->stages->firstWhere('stage', $stage);
    }

    public function stageOrCreate(string $stage): CuttingStage
    {
        return $this->stages()->firstOrCreate(
            ['stage' => $stage],
            ['status' => 'draft']
        );
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function scopeVisibleTo($query, User $user)
    {
        $isMerchandiserOnly = $user->hasRole('merchandiser')
            && ! $user->hasAnyRole(['admin', 'manager', 'qam', 'management', 'cutting_qc', 'cutting_incharge']);

        if ($isMerchandiserOnly) {
            $buyerIds = $user->assignedBuyers()->pluck('buyers.id');
            $query->whereIn('buyer_id', $buyerIds);
        }

        return $query;
    }

    public function actualLengthOutOfTolerance(): bool
    {
        if (! $this->required_lay_length || ! $this->actual_lay_length) {
            return false;
        }
        $diff = abs((float) $this->actual_lay_length - (float) $this->required_lay_length);
        $allowed = (float) $this->required_lay_length * ((float) $this->tolerance_pct / 100);

        return $diff > $allowed;
    }

    public function actualWidthOutOfTolerance(): bool
    {
        if (! $this->required_lay_width || ! $this->actual_lay_width) {
            return false;
        }
        $diff = abs((float) $this->actual_lay_width - (float) $this->required_lay_width);
        $allowed = (float) $this->required_lay_width * ((float) $this->tolerance_pct / 100);

        return $diff > $allowed;
    }

    public function pliesMismatch(): bool
    {
        return $this->required_plies !== null
            && $this->actual_plies !== null
            && (int) $this->required_plies !== (int) $this->actual_plies;
    }
}
