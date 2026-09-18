<?php

namespace App\Http\Controllers\Admin;

use App\Exports\StyleTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\StyleImport;
use App\Models\Buyer;
use App\Models\Style;
use App\Models\UploadBatch;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StyleController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'buyer_id', 'status']);

        $styles = Style::with('buyer')
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where('style_number', 'like', "%{$v}%")
                  ->orWhere('fabric_type', 'like', "%{$v}%")
                  ->orWhere('color', 'like', "%{$v}%");
            })
            ->when($filters['buyer_id'] ?? null, fn ($q, $v) => $q->where('buyer_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('style_number')
            ->paginate(20)
            ->withQueryString();

        $buyers = Buyer::where('is_active', true)->orderBy('buyer_name')->get();
        $importResult = session('import_result');
        return view('admin.styles.index', compact('styles', 'buyers', 'filters', 'importResult'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Style::class);
        $data = $request->validate([
            'style_number' => 'required|string|max:50|unique:styles,style_number',
            'buyer_id' => 'required|exists:buyers,id',
            'order_quantity' => 'required|numeric|min:0',
            'target_date' => 'required|date',
            'status' => 'nullable|in:planning,in_progress,completed,on_hold',
            'fabric_type' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:100',
            'gsm_target' => 'nullable|numeric|min:0',
            'width_target' => 'nullable|numeric|min:0',
        ]);
        $data['status'] = $data['status'] ?? 'planning';
        Style::create($data);
        return redirect()->route('admin.styles.index')->with('success', 'Style added.');
    }

    public function update(Request $request, Style $style)
    {
        $this->authorize('update', $style);
        $data = $request->validate([
            'style_number' => 'required|string|max:50|unique:styles,style_number,' . $style->id,
            'buyer_id' => 'required|exists:buyers,id',
            'order_quantity' => 'required|numeric|min:0',
            'target_date' => 'required|date',
            'status' => 'nullable|in:planning,in_progress,completed,on_hold',
            'fabric_type' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:100',
            'gsm_target' => 'nullable|numeric|min:0',
            'width_target' => 'nullable|numeric|min:0',
        ]);
        $style->update($data);
        return redirect()->route('admin.styles.index')->with('success', 'Style updated.');
    }

    public function destroy(Style $style)
    {
        $this->authorize('delete', $style);
        $style->delete();
        return redirect()->route('admin.styles.index')->with('success', 'Style deleted.');
    }

    public function import(Request $request)
    {
        $this->authorize('create', Style::class);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $import = new StyleImport();
        Excel::import($import, $request->file('file'));

        UploadBatch::create([
            'file_name' => $request->file('file')->getClientOriginalName(),
            'upload_type' => 'style_import',
            'uploaded_by' => auth()->id(),
            'status' => 'completed',
            'total_rows' => $import->successCount + count($import->errors),
            'success_rows' => $import->successCount,
            'error_rows' => count($import->errors),
            'error_log' => $import->errors ?: null,
        ]);

        return redirect()->route('admin.styles.index')
            ->with('import_result', [
                'success' => $import->successCount,
                'errors' => $import->errors,
            ]);
    }

    public function template()
    {
        return Excel::download(new StyleTemplateExport(), 'style-upload-template.xlsx');
    }
}
