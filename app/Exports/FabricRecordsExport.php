<?php

namespace App\Exports;

use App\Models\FabricRecord;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FabricRecordsExport implements FromQuery, WithHeadings, WithMapping
{
    protected array $filters = [];

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        return FabricRecord::query()->with(['buyer', 'style', 'supplier', 'inspection'])
            ->when($this->filters['buyer_id'] ?? null, fn ($q, $v) => $q->where('buyer_id', $v))
            ->when($this->filters['style_id'] ?? null, fn ($q, $v) => $q->where('style_id', $v))
            ->when($this->filters['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($this->filters['fabric_type'] ?? null, fn ($q, $v) => $q->where('fabric_type', $v))
            ->when($this->filters['color'] ?? null, fn ($q, $v) => $q->where('color', $v))
            ->when($this->filters['from'] ?? null, fn ($q, $v) => $q->whereDate('record_date', '>=', $v))
            ->when($this->filters['to'] ?? null, fn ($q, $v) => $q->whereDate('record_date', '<=', $v))
            ->orderByDesc('record_date');
    }

    public function headings(): array
    {
        return ['Date', 'Lot No', 'Buyer', 'Style', 'Supplier', 'Fabric Type', 'Color', 'Ordered Kg', 'Received Kg', 'Inspected Kg', 'Approved Kg', 'Rejected Kg', 'Pass %', 'Shade Status'];
    }

    public function map($record): array
    {
        $insp = $record->inspection;
        return [
            $record->record_date?->format('Y-m-d'),
            $record->lot_no,
            $record->buyer?->buyer_name,
            $record->style?->style_number,
            $record->supplier?->supplier_name,
            $record->fabric_type,
            $record->color,
            $record->ordered_kg,
            $record->received_kg,
            $insp?->inspected_kg,
            $insp?->approved_kg,
            $insp?->rejected_kg,
            $insp?->pass_pct,
            $insp?->shade_status,
        ];
    }
}
