<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Exports\SewingDefectTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\SewingDefectImport;
use App\Models\SewingDefectMaster;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class SewingDefectMasterController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);
        $defects = SewingDefectMaster::query()
            ->when($request->filled('type'), fn ($q) => $q->where('defect_type', $request->input('type')))
            ->orderBy('defect_type')->orderBy('capa_code')
            ->paginate(50)
            ->withQueryString();

        $types = SewingDefectMaster::TYPES;

        return view('admin.sewing.settings.defects', compact('defects', 'types'));
    }

    public function store(Request $request)
    {
        $this->guard($request);
        $data = $this->validated($request);
        $data['is_active'] = true;
        SewingDefectMaster::create($data);

        return back()->with('success', 'Defect master row added.');
    }

    public function update(Request $request, SewingDefectMaster $defect)
    {
        $this->guard($request);
        $data = $this->validated($request, $defect);
        $data['is_active'] = $request->boolean('is_active', $defect->is_active);
        $defect->update($data);

        return back()->with('success', 'Defect master row updated.');
    }

    public function destroy(Request $request, SewingDefectMaster $defect)
    {
        $this->guard($request);
        $defect->update(['is_active' => false]);

        return back()->with('success', 'Defect master row retired.');
    }

    public function import(Request $request)
    {
        $this->guard($request);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $import = new SewingDefectImport;
        Excel::import($import, $request->file('file'));

        return back()->with('import_result', [
            'success' => $import->successCount,
            'errors' => $import->errors,
        ]);
    }

    public function template(Request $request)
    {
        $this->guard($request);

        return Excel::download(new SewingDefectTemplateExport, 'sewing-defect-dcl-template.xlsx');
    }

    protected function validated(Request $request, ?SewingDefectMaster $defect = null): array
    {
        return $request->validate([
            'defect_type' => ['required', Rule::in(array_keys(SewingDefectMaster::TYPES))],
            'capa_code' => ['required', 'string', 'max:10', Rule::unique('sewing_defect_masters', 'capa_code')->ignore($defect?->id)],
            'defect_name' => 'required|string|max:191',
            'root_cause' => 'required|string',
            'corrective_action' => 'required|string',
            'preventive_action' => 'required|string',
        ]);
    }

    protected function guard(Request $request): void
    {
        abort_unless($request->user()->can('manage sewing masters'), 403);
    }
}
