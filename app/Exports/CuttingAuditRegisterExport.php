<?php

namespace App\Exports;

use App\Models\CuttingAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CuttingAuditRegisterExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(protected array $filters = [], protected ?User $user = null) {}

    public function query(): Builder
    {
        $query = CuttingAudit::query()
            ->with(['cuttingTable', 'buyer', 'stages'])
            ->when($this->filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('audit_no', 'like', "%{$v}%")
                        ->orWhere('style_number', 'like', "%{$v}%")
                        ->orWhere('po_number', 'like', "%{$v}%")
                        ->orWhere('lot_number', 'like', "%{$v}%");
                });
            })
            ->when($this->filters['from'] ?? null, fn ($q, $v) => $q->whereDate('audit_date', '>=', $v))
            ->when($this->filters['to'] ?? null, fn ($q, $v) => $q->whereDate('audit_date', '<=', $v))
            ->when($this->filters['buyer_id'] ?? null, fn ($q, $v) => $q->where('buyer_id', $v))
            ->when($this->filters['cutting_table_id'] ?? null, fn ($q, $v) => $q->where('cutting_table_id', $v))
            ->when($this->filters['shift'] ?? null, fn ($q, $v) => $q->where('shift', $v))
            ->when($this->filters['stage'] ?? null, fn ($q, $v) => $q->whereHas('stages', fn ($s) => $s->where('stage', $v)))
            ->when($this->filters['result'] ?? null, fn ($q, $v) => $q->whereHas('stages', fn ($s) => $s->where('result', $v)))
            ->orderByDesc('audit_date');

        if ($this->user) {
            $query->visibleTo($this->user);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Audit ID', 'Date', 'Shift', 'Table', 'Buyer', 'Style#', 'Style Name', 'PO#', 'LOT#',
            'Fabric', 'Colour', 'DIA', 'Lay#', 'Cut#', 'Revision',
            'Lay Status', 'Lay Result', 'Cut Panel Status', 'Cut Panel Result',
            'Bundling Status', 'Bundling Result', 'Audit Status',
        ];
    }

    public function map($audit): array
    {
        $stage = fn ($s) => $audit->stages->firstWhere('stage', $s);

        return [
            $audit->audit_no,
            $audit->audit_date?->format('Y-m-d'),
            $audit->shift,
            $audit->cuttingTable?->table_no,
            $audit->buyer?->buyer_name,
            $audit->style_number,
            $audit->style_name,
            $audit->po_number,
            $audit->lot_number,
            $audit->fabric,
            $audit->colour,
            $audit->dia,
            $audit->lay_number,
            $audit->cut_number,
            $audit->revision,
            $stage('lay')?->status,
            $stage('lay')?->result,
            $stage('cut_panel')?->status,
            $stage('cut_panel')?->result,
            $stage('bundling')?->status,
            $stage('bundling')?->result,
            $audit->status,
        ];
    }
}
