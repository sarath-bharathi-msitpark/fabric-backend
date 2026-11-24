<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Http\Controllers\Controller;
use App\Models\SewingInspectionRule;
use Illuminate\Http\Request;

class SewingRuleController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);
        $rule = SewingInspectionRule::current();

        return view('admin.sewing.settings.rules', compact('rule'));
    }

    public function update(Request $request)
    {
        $this->guard($request);
        $data = $request->validate([
            'pieces_per_audit' => 'required|integer|min:1|max:20',
            'green_max' => 'required|integer|min:0|max:20',
            'amber_max' => 'required|integer|min:0|max:20|gt:green_max',
            'default_rounds' => 'required|integer|min:1|max:12',
        ]);

        $rule = SewingInspectionRule::where('is_active', true)->latest('version')->first();

        if ($rule) {
            $rule->update($data);
        } else {
            SewingInspectionRule::create($data + ['version' => 1, 'is_active' => true]);
        }

        return back()->with('success', 'Inspection rules saved.');
    }

    protected function guard(Request $request): void
    {
        abort_unless($request->user()->can('manage sewing rules'), 403);
    }
}
