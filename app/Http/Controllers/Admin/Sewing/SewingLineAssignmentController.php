<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Http\Controllers\Controller;
use App\Models\SewingLine;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SewingLineAssignmentController extends Controller
{
    public const ASSIGNABLE_ROLES = ['sewing_qc', 'sewing_supervisor', 'production_manager'];

    public function index(Request $request)
    {
        $this->guard($request);

        $lines = SewingLine::where('is_active', true)->orderBy('line_no')->get();

        $users = User::whereHas('roles', fn ($r) => $r->whereIn('name', self::ASSIGNABLE_ROLES))
            ->orderBy('name')
            ->get();

        $assigned = DB::table('sewing_line_user')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('sewing_line_id')->all());

        return view('admin.sewing.settings.line-assignments', compact('lines', 'users', 'assigned'));
    }

    public function store(Request $request)
    {
        $this->guard($request);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'sewing_line_ids' => 'nullable|array',
            'sewing_line_ids.*' => 'exists:sewing_lines,id',
        ]);

        $lineIds = $data['sewing_line_ids'] ?? [];

        DB::transaction(function () use ($data, $lineIds) {
            DB::table('sewing_line_user')->where('user_id', $data['user_id'])->delete();

            $now = now();
            foreach ($lineIds as $lineId) {
                DB::table('sewing_line_user')->insert([
                    'user_id' => $data['user_id'],
                    'sewing_line_id' => $lineId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        return back()->with('success', 'Line assignments updated.');
    }

    protected function guard(Request $request): void
    {
        abort_unless($request->user()->can('manage sewing masters'), 403);
    }
}
