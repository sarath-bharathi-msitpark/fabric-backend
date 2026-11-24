<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Http\Controllers\Controller;
use App\Models\SewingMachine;
use Illuminate\Http\Request;

class SewingMachineController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);
        $machines = SewingMachine::orderBy('machine_no')->paginate(30);

        return view('admin.sewing.settings.machines', compact('machines'));
    }

    public function store(Request $request)
    {
        $this->guard($request);
        $data = $request->validate([
            'machine_no' => 'required|string|max:50|unique:sewing_machines,machine_no',
            'type' => 'nullable|string|max:191',
        ]);
        $data['is_active'] = true;
        SewingMachine::create($data);

        return back()->with('success', 'Machine added.');
    }

    public function update(Request $request, SewingMachine $machine)
    {
        $this->guard($request);
        $data = $request->validate([
            'machine_no' => 'required|string|max:50|unique:sewing_machines,machine_no,'.$machine->id,
            'type' => 'nullable|string|max:191',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', $machine->is_active);
        $machine->update($data);

        return back()->with('success', 'Machine updated.');
    }

    public function destroy(Request $request, SewingMachine $machine)
    {
        $this->guard($request);
        $machine->update(['is_active' => false]);

        return back()->with('success', 'Machine deactivated.');
    }

    protected function guard(Request $request): void
    {
        abort_unless($request->user()->can('manage sewing masters'), 403);
    }
}
