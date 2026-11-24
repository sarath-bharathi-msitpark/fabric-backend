<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Http\Controllers\Controller;
use App\Models\SewingRoundConfig;
use Illuminate\Http\Request;

class SewingRoundConfigController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);
        $rounds = SewingRoundConfig::orderBy('round_no')->get();

        return view('admin.sewing.settings.rounds', compact('rounds'));
    }

    public function update(Request $request)
    {
        $this->guard($request);
        $data = $request->validate([
            'rounds' => 'required|array|min:1',
            'rounds.*.round_no' => 'required|integer|min:1|max:12',
            'rounds.*.start_time' => 'nullable|date_format:H:i',
            'rounds.*.end_time' => 'nullable|date_format:H:i',
            'rounds.*.is_active' => 'nullable|boolean',
        ]);

        foreach ($data['rounds'] as $row) {
            SewingRoundConfig::updateOrCreate(
                ['round_no' => $row['round_no']],
                [
                    'start_time' => $row['start_time'] ?? null,
                    'end_time' => $row['end_time'] ?? null,
                    'is_active' => (bool) ($row['is_active'] ?? false),
                ]
            );
        }

        return back()->with('success', 'Round configuration saved.');
    }

    protected function guard(Request $request): void
    {
        abort_unless($request->user()->can('manage sewing rules'), 403);
    }
}
