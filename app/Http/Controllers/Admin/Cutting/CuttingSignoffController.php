<?php

namespace App\Http\Controllers\Admin\Cutting;

use App\Http\Controllers\Controller;
use App\Models\CuttingAudit;
use App\Models\CuttingSpecialApproval;
use App\Services\CuttingWorkflowService;
use Illuminate\Http\Request;

class CuttingSignoffController extends Controller
{
    public function __construct(protected CuttingWorkflowService $workflow) {}

    public function signoff(Request $request, CuttingAudit $audit, string $stage)
    {
        $this->authorize('signoff', [$audit, $stage]);

        $data = $request->validate([
            'comments' => 'nullable|string|max:2000',
            'reason' => 'nullable|string|max:2000',
        ]);

        $this->workflow->signOff($audit, $stage, $request->user(), $data['comments'] ?? null, $data['reason'] ?? null);

        return redirect()
            ->route('admin.cutting.audits.show', $audit)
            ->with('success', 'Sign-off recorded.');
    }

    public function decide(Request $request, CuttingSpecialApproval $approval)
    {
        $this->authorize('specialApprove', CuttingAudit::class);

        $data = $request->validate([
            'decision' => 'required|in:approve,reject',
            'decision_reason' => 'nullable|string|max:2000',
        ]);

        $this->workflow->decideSpecialApproval(
            $approval,
            $request->user(),
            $data['decision'] === 'approve',
            $data['decision_reason'] ?? null
        );

        return redirect()
            ->route('admin.cutting.audits.show', $approval->cutting_audit_id)
            ->with('success', 'Special approval decision recorded.');
    }

    public function revision(Request $request, CuttingAudit $audit)
    {
        $this->authorize('update', $audit);

        $data = $request->validate([
            'stage' => 'required|in:lay,cut_panel,bundling',
            'reason' => 'nullable|string|max:2000',
        ]);

        $new = $this->workflow->requestRevision($audit, $data['stage'], $request->user(), $data['reason'] ?? null);

        return redirect()
            ->route('admin.cutting.stages.show', [$new, $data['stage']])
            ->with('success', "Revision {$new->revision} created for reinspection.");
    }

    public function release(Request $request, CuttingAudit $audit)
    {
        $this->authorize('release', $audit);

        $this->workflow->releaseBundles($audit, $request->user());

        return redirect()
            ->route('admin.cutting.audits.show', $audit)
            ->with('success', 'Bundles released to sewing. Audit closed and locked.');
    }
}
