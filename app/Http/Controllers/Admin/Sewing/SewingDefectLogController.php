<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Http\Controllers\Controller;
use App\Models\SewingDefectLog;
use App\Models\SewingDefectMaster;
use Illuminate\Http\Request;

class SewingDefectLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = SewingDefectLog::query()
            ->with(['sheet', 'operation', 'defectMaster', 'loggedBy'])
            ->when($request->filled('type'), fn ($q) => $q->where('defect_type', $request->input('type')))
            ->when($request->filled('capa_code'), fn ($q) => $q->where('capa_code', 'like', '%'.$request->input('capa_code').'%'))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $types = SewingDefectMaster::TYPES;

        return view('admin.sewing.defect-log.index', compact('logs', 'types'));
    }
}
