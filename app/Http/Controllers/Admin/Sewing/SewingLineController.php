<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Http\Controllers\Controller;
use App\Models\SewingLine;
use Illuminate\Http\Request;

class SewingLineController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);
        $lines = SewingLine::orderBy('line_no')->paginate(30);

        return view('admin.sewing.settings.lines', compact('lines'));
    }

    public function store(Request $request)
    {
        $this->guard($request);
        $data = $request->validate([
            'line_no' => 'required|string|max:50|unique:sewing_lines,line_no',
            'name' => 'nullable|string|max:191',
        ]);
        $data['is_active'] = true;
        SewingLine::create($data);

        return back()->with('success', 'Line added.');
    }

    public function update(Request $request, SewingLine $line)
    {
        $this->guard($request);
        $data = $request->validate([
            'line_no' => 'required|string|max:50|unique:sewing_lines,line_no,'.$line->id,
            'name' => 'nullable|string|max:191',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', $line->is_active);
        $line->update($data);

        return back()->with('success', 'Line updated.');
    }

    public function destroy(Request $request, SewingLine $line)
    {
        $this->guard($request);
        $line->update(['is_active' => false]);

        return back()->with('success', 'Line deactivated.');
    }

    protected function guard(Request $request): void
    {
        abort_unless($request->user()->can('manage sewing masters'), 403);
    }
}
