<?php

namespace App\Http\Controllers\Admin\Cutting;

use App\Http\Controllers\Controller;
use App\Models\CuttingAudit;
use App\Models\CuttingChecklistTemplate;
use App\Models\CuttingCheckpoint;
use App\Models\CuttingNumberSequence;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class CuttingChecklistController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manageChecklists', CuttingAudit::class);

        $templates = collect(CuttingAudit::STAGES)->mapWithKeys(function ($stage) {
            return [$stage => CuttingChecklistTemplate::where('stage', $stage)
                ->with(['checkpoints' => fn ($q) => $q->orderBy('sort_order')])
                ->orderByDesc('version')
                ->get()];
        });

        $roles = Role::orderBy('name')->pluck('name');
        $sequence = CuttingNumberSequence::where('key', 'cutting_audit')->first();

        return view('admin.cutting.checklists.index', compact('templates', 'roles', 'sequence'));
    }

    public function updateTemplate(Request $request, CuttingChecklistTemplate $template)
    {
        $this->authorize('manageChecklists', CuttingAudit::class);

        $data = $request->validate([
            'signoff_roles' => 'nullable|array',
            'signoff_roles.*' => 'string',
        ]);

        $template->update(['signoff_roles' => $data['signoff_roles'] ?? []]);

        return back()->with('success', 'Sign-off roles updated for this checklist version.');
    }

    public function newVersion(Request $request, CuttingChecklistTemplate $template)
    {
        $this->authorize('manageChecklists', CuttingAudit::class);

        $nextVersion = (int) CuttingChecklistTemplate::where('stage', $template->stage)->max('version') + 1;

        $new = CuttingChecklistTemplate::create([
            'stage' => $template->stage,
            'version' => $nextVersion,
            'signoff_roles' => $template->signoff_roles,
            'is_active' => true,
        ]);

        foreach ($template->checkpoints()->orderBy('sort_order')->get() as $cp) {
            $new->checkpoints()->create([
                'name' => $cp->name,
                'description' => $cp->description,
                'sort_order' => $cp->sort_order,
                'mandatory_remarks_on_not_ok' => $cp->mandatory_remarks_on_not_ok,
                'blocks_approval' => $cp->blocks_approval,
                'is_active' => $cp->is_active,
            ]);
        }

        // Only the newest version stays active.
        CuttingChecklistTemplate::where('stage', $template->stage)
            ->where('id', '!=', $new->id)
            ->update(['is_active' => false]);

        return back()->with('success', "Checklist version {$nextVersion} created for {$template->stage}.");
    }

    public function storeCheckpoint(Request $request, CuttingChecklistTemplate $template)
    {
        $this->authorize('manageChecklists', CuttingAudit::class);

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string|max:1000',
            'mandatory_remarks_on_not_ok' => 'nullable|boolean',
            'blocks_approval' => 'nullable|boolean',
        ]);

        $order = (int) $template->checkpoints()->max('sort_order') + 1;

        $template->checkpoints()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order' => $order,
            'mandatory_remarks_on_not_ok' => $request->boolean('mandatory_remarks_on_not_ok'),
            'blocks_approval' => $request->boolean('blocks_approval'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Checkpoint added.');
    }

    public function updateCheckpoint(Request $request, CuttingCheckpoint $checkpoint)
    {
        $this->authorize('manageChecklists', CuttingAudit::class);

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string|max:1000',
            'mandatory_remarks_on_not_ok' => 'nullable|boolean',
            'blocks_approval' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $checkpoint->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'mandatory_remarks_on_not_ok' => $request->boolean('mandatory_remarks_on_not_ok'),
            'blocks_approval' => $request->boolean('blocks_approval'),
            'is_active' => $request->boolean('is_active', $checkpoint->is_active),
        ]);

        return back()->with('success', 'Checkpoint updated. Historical audits are unaffected.');
    }

    public function destroyCheckpoint(Request $request, CuttingCheckpoint $checkpoint)
    {
        $this->authorize('manageChecklists', CuttingAudit::class);

        // Retire, never delete — historical results reference this checkpoint.
        $checkpoint->update(['is_active' => false]);

        return back()->with('success', 'Checkpoint retired. Historical audits keep their data.');
    }

    public function moveCheckpoint(Request $request, CuttingCheckpoint $checkpoint)
    {
        $this->authorize('manageChecklists', CuttingAudit::class);

        $direction = $request->input('direction') === 'up' ? 'up' : 'down';

        $siblings = CuttingCheckpoint::where('cutting_checklist_template_id', $checkpoint->cutting_checklist_template_id)
            ->orderBy('sort_order')
            ->get();

        $index = $siblings->search(fn ($c) => $c->id === $checkpoint->id);
        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swapIndex >= 0 && $swapIndex < $siblings->count()) {
            $other = $siblings[$swapIndex];
            $tmp = $checkpoint->sort_order;
            $checkpoint->update(['sort_order' => $other->sort_order]);
            $other->update(['sort_order' => $tmp]);
        }

        return back()->with('success', 'Checkpoint order updated.');
    }

    public function updateNumbering(Request $request)
    {
        $this->authorize('manageChecklists', CuttingAudit::class);

        $data = $request->validate([
            'prefix' => 'required|string|max:20',
            'padding' => 'required|integer|min:1|max:10',
        ]);

        $sequence = CuttingNumberSequence::firstOrCreate(['key' => 'cutting_audit']);
        $sequence->update($data);

        return back()->with('success', 'Audit numbering updated.');
    }
}
