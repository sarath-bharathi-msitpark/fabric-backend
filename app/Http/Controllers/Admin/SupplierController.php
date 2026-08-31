<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FabricRecord;
use App\Models\Supplier;
use App\Services\SupplierRatingService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::orderBy('supplier_name')->paginate(20);
        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Supplier::class);
        $data = $request->validate([
            'supplier_name' => 'required|string|max:100|unique:suppliers,supplier_name',
            'mill_code' => 'nullable|string|max:30',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
        ]);
        $data['is_active'] = true;
        Supplier::create($data);
        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier added.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load('fabricRecords.inspection', 'fabricRecords.style');

        $trend = collect(range(5, 0))->map(function ($monthsAgo) use ($supplier) {
            $date = now()->subMonths($monthsAgo);
            $onTime = FabricRecord::where('supplier_id', $supplier->id)
                ->whereMonth('record_date', $date->month)
                ->whereYear('record_date', $date->year)
                ->whereHas('style', fn ($q) => $q->whereColumn('target_date', '>=', 'fabric_records.record_date'))
                ->count();
            $total = FabricRecord::where('supplier_id', $supplier->id)
                ->whereMonth('record_date', $date->month)
                ->whereYear('record_date', $date->year)
                ->count();
            return [
                'month' => $date->format('M Y'),
                'on_time_pct' => $total > 0 ? round(($onTime / $total) * 100, 2) : 0,
                'quality_pct' => (float) $supplier->quality_pct,
            ];
        });

        return view('admin.suppliers.show', compact('supplier', 'trend'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);
        $data = $request->validate([
            'supplier_name' => 'required|string|max:100|unique:suppliers,supplier_name,' . $supplier->id,
            'mill_code' => 'nullable|string|max:30',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
        ]);
        $supplier->update($data);
        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier updated.');
    }

    public function toggleActive(Request $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);
        $supplier->update(['is_active' => !$supplier->is_active]);
        return response()->json(['success' => true, 'is_active' => $supplier->is_active]);
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorize('delete', $supplier);
        $supplier->delete();
        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier deleted.');
    }
}
