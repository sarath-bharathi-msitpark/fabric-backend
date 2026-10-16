<?php

namespace App\Http\Controllers\Admin\Cutting;

use App\Http\Controllers\Controller;
use App\Models\CuttingAudit;
use App\Models\CuttingTable;
use Illuminate\Http\Request;

class CuttingTableController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manageChecklists', CuttingAudit::class);
        $tables = CuttingTable::orderBy('table_no')->paginate(30);

        return view('admin.cutting.tables.index', compact('tables'));
    }

    public function store(Request $request)
    {
        $this->authorize('manageChecklists', CuttingAudit::class);
        $data = $request->validate([
            'table_no' => 'required|string|max:50|unique:cutting_tables,table_no',
            'name' => 'nullable|string|max:191',
        ]);
        $data['is_active'] = true;
        CuttingTable::create($data);

        return back()->with('success', 'Cutting table added.');
    }

    public function update(Request $request, CuttingTable $table)
    {
        $this->authorize('manageChecklists', CuttingAudit::class);
        $data = $request->validate([
            'table_no' => 'required|string|max:50|unique:cutting_tables,table_no,'.$table->id,
            'name' => 'nullable|string|max:191',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', $table->is_active);
        $table->update($data);

        return back()->with('success', 'Cutting table updated.');
    }

    public function destroy(Request $request, CuttingTable $table)
    {
        $this->authorize('manageChecklists', CuttingAudit::class);
        $table->update(['is_active' => false]);

        return back()->with('success', 'Cutting table deactivated.');
    }
}
