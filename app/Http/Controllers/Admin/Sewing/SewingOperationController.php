<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Http\Controllers\Controller;
use App\Models\SewingMachine;
use App\Models\SewingOperation;
use Illuminate\Http\Request;

class SewingOperationController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);
        $operations = SewingOperation::with('defaultMachine')->orderBy('name')->paginate(30);
        $machines = SewingMachine::where('is_active', true)->orderBy('machine_no')->get();

        return view('admin.sewing.settings.operations', compact('operations', 'machines'));
    }

    public function store(Request $request)
    {
        $this->guard($request);
        $data = $request->validate([
            'name' => 'required|string|max:191|unique:sewing_operations,name',
            'default_machine_id' => 'nullable|exists:sewing_machines,id',
        ]);
        $data['is_active'] = true;
        SewingOperation::create($data);

        return back()->with('success', 'Operation added.');
    }

    public function update(Request $request, SewingOperation $operation)
    {
        $this->guard($request);
        $data = $request->validate([
            'name' => 'required|string|max:191|unique:sewing_operations,name,'.$operation->id,
            'default_machine_id' => 'nullable|exists:sewing_machines,id',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', $operation->is_active);
        $operation->update($data);

        return back()->with('success', 'Operation updated.');
    }

    public function destroy(Request $request, SewingOperation $operation)
    {
        $this->guard($request);
        $operation->update(['is_active' => false]);

        return back()->with('success', 'Operation deactivated.');
    }

    protected function guard(Request $request): void
    {
        abort_unless($request->user()->can('manage sewing masters'), 403);
    }
}
