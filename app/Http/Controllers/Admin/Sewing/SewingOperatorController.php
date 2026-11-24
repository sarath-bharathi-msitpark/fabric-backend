<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Http\Controllers\Controller;
use App\Models\SewingLine;
use App\Models\SewingOperator;
use Illuminate\Http\Request;

class SewingOperatorController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);
        $operators = SewingOperator::with('line')->orderBy('name')->paginate(30);
        $lines = SewingLine::where('is_active', true)->orderBy('line_no')->get();

        return view('admin.sewing.settings.operators', compact('operators', 'lines'));
    }

    public function store(Request $request)
    {
        $this->guard($request);
        $data = $request->validate([
            'employee_code' => 'required|string|max:50|unique:sewing_operators,employee_code',
            'name' => 'required|string|max:191',
            'sewing_line_id' => 'nullable|exists:sewing_lines,id',
        ]);
        $data['is_active'] = true;
        SewingOperator::create($data);

        return back()->with('success', 'Operator added.');
    }

    public function update(Request $request, SewingOperator $operator)
    {
        $this->guard($request);
        $data = $request->validate([
            'employee_code' => 'required|string|max:50|unique:sewing_operators,employee_code,'.$operator->id,
            'name' => 'required|string|max:191',
            'sewing_line_id' => 'nullable|exists:sewing_lines,id',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', $operator->is_active);
        $operator->update($data);

        return back()->with('success', 'Operator updated.');
    }

    public function destroy(Request $request, SewingOperator $operator)
    {
        $this->guard($request);
        $operator->update(['is_active' => false]);

        return back()->with('success', 'Operator deactivated.');
    }

    protected function guard(Request $request): void
    {
        abort_unless($request->user()->can('manage sewing masters'), 403);
    }
}
