<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Http\Controllers\Controller;
use App\Models\SewingMachine;
use App\Models\SewingOperation;
use App\Models\SewingStyleOperation;
use App\Models\Style;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SewingStyleOperationController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);

        $styles = Style::orderBy('style_number')->get();
        $selectedId = $request->integer('style_id') ?: $styles->first()?->id;
        $selected = $selectedId ? $styles->firstWhere('id', $selectedId) : null;

        $styleOps = $selected
            ? SewingStyleOperation::with(['operation', 'machine'])
                ->where('style_id', $selected->id)
                ->orderBy('sequence_no')
                ->get()
            : collect();

        $operations = SewingOperation::where('is_active', true)->orderBy('name')->get();
        $machines = SewingMachine::where('is_active', true)->orderBy('machine_no')->get();

        return view('admin.sewing.settings.style-operations', compact(
            'styles', 'selected', 'styleOps', 'operations', 'machines'
        ));
    }

    public function store(Request $request)
    {
        $this->guard($request);

        $data = $request->validate([
            'style_id' => 'required|exists:styles,id',
            'sewing_operation_id' => 'required|exists:sewing_operations,id',
            'sewing_machine_id' => 'nullable|exists:sewing_machines,id',
        ]);

        $data['sequence_no'] = (int) SewingStyleOperation::where('style_id', $data['style_id'])->max('sequence_no') + 1;
        $data['is_active'] = true;
        SewingStyleOperation::create($data);

        return redirect()
            ->route('admin.sewing.settings.style-operations.index', ['style_id' => $data['style_id']])
            ->with('success', 'Operation added to the style sequence.');
    }

    public function move(Request $request, SewingStyleOperation $styleOperation)
    {
        $this->guard($request);

        $direction = $request->validate(['direction' => 'required|in:up,down'])['direction'];

        $siblings = SewingStyleOperation::where('style_id', $styleOperation->style_id)
            ->orderBy('sequence_no')
            ->get();

        $index = $siblings->search(fn ($o) => $o->id === $styleOperation->id);
        $swap = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || $swap < 0 || $swap >= $siblings->count()) {
            return back();
        }

        DB::transaction(function () use ($siblings, $index, $swap) {
            $siblings->each(fn ($o, $i) => $o->update(['sequence_no' => $i + 1]));
            $siblings[$index]->update(['sequence_no' => $swap + 1]);
            $siblings[$swap]->update(['sequence_no' => $index + 1]);
        });

        return redirect()->route('admin.sewing.settings.style-operations.index', ['style_id' => $styleOperation->style_id]);
    }

    public function destroy(Request $request, SewingStyleOperation $styleOperation)
    {
        $this->guard($request);

        $styleId = $styleOperation->style_id;
        $styleOperation->delete();

        SewingStyleOperation::where('style_id', $styleId)
            ->orderBy('sequence_no')
            ->get()
            ->each(fn ($o, $i) => $o->update(['sequence_no' => $i + 1]));

        return redirect()
            ->route('admin.sewing.settings.style-operations.index', ['style_id' => $styleId])
            ->with('success', 'Operation removed from the style sequence.');
    }

    protected function guard(Request $request): void
    {
        abort_unless($request->user()->can('manage sewing masters'), 403);
    }
}
