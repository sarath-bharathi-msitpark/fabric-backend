<?php

namespace App\Http\Controllers\Admin\Cutting;

use App\Http\Controllers\Controller;
use App\Models\CuttingAudit;
use App\Models\CuttingChecklistTemplate;
use App\Models\CuttingCheckpointResult;
use App\Models\CuttingStage;
use App\Services\CuttingWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CuttingStageController extends Controller
{
    public function __construct(protected CuttingWorkflowService $workflow) {}

    public function show(Request $request, CuttingAudit $audit, string $stage)
    {
        $this->authorize('view', $audit);
        $this->ensureStage($stage);

        $audit->load(['cuttingTable', 'buyer', 'sizePatterns', 'bundles']);
        $stageModel = $audit->stages()->where('stage', $stage)->with(['results.photos', 'signoffs'])->first();
        $currentTemplate = CuttingChecklistTemplate::currentFor($stage);
        $stageTemplate = $stageModel?->template;

        $checkpoints = $this->checkpointRows($stageModel, $currentTemplate, $stageTemplate);
        $sizeRows = $this->sizeRows($audit, $stage);
        $unlocked = $this->workflow->isStageUnlocked($audit, $stage);
        $nextRole = $stageModel ? $this->workflow->nextExpectedRole($audit, $stage) : null;
        $requiredRoles = $this->workflow->requiredSignoffRoles($stage);
        $resultOptions = CuttingAudit::RESULTS[$stage];

        return view('admin.cutting.stages.show', compact(
            'audit', 'stage', 'stageModel', 'checkpoints', 'sizeRows',
            'unlocked', 'nextRole', 'requiredRoles', 'resultOptions'
        ));
    }

    public function draft(Request $request, CuttingAudit $audit, string $stage)
    {
        $this->authorize('submitStage', $audit);
        $this->ensureStage($stage);

        $payload = $this->buildPayload($request, $stage);
        $this->workflow->saveStage($audit, $stage, $payload, $request->user(), false);

        return redirect()
            ->route('admin.cutting.stages.show', [$audit, $stage])
            ->with('success', 'Draft saved. You can resume this stage later.');
    }

    public function submit(Request $request, CuttingAudit $audit, string $stage)
    {
        $this->authorize('submitStage', $audit);
        $this->ensureStage($stage);

        $request->validate(['result' => 'required|string']);

        $payload = $this->buildPayload($request, $stage);
        $this->workflow->saveStage($audit, $stage, $payload, $request->user(), true);

        return redirect()
            ->route('admin.cutting.audits.show', $audit)
            ->with('success', 'Stage submitted and locked. Sign-offs will now be captured.');
    }

    protected function buildPayload(Request $request, string $stage): array
    {
        $checkpoints = [];
        foreach ($request->input('checkpoints', []) as $index => $row) {
            $photoPaths = [];
            $files = $request->file("checkpoints.{$index}.photos");
            if ($files) {
                foreach ((array) $files as $file) {
                    if ($file && $file->isValid()) {
                        $photoPaths[] = $file->store('cutting/checkpoints', 'public');
                    }
                }
            }

            $checkpoints[] = [
                'checkpoint_id' => $row['checkpoint_id'] ?? null,
                'checkpoint_name' => $row['checkpoint_name'] ?? null,
                'result' => $row['result'] ?? null,
                'remarks' => $row['remarks'] ?? null,
                'mandatory_remarks_on_not_ok' => $row['mandatory_remarks_on_not_ok'] ?? false,
                'photo_paths' => $photoPaths,
            ];
        }

        return [
            'comments' => $request->input('comments'),
            'result' => $request->input('result'),
            'special_reason' => $request->input('special_reason'),
            'inspection_date' => $request->input('inspection_date') ?: null,
            'checkpoints' => $checkpoints,
            'size_patterns' => $request->input('size_patterns', []),
            'bundles' => $request->input('bundles', []),
        ];
    }

    protected function checkpointRows(
        ?CuttingStage $stageModel,
        ?CuttingChecklistTemplate $currentTemplate,
        ?CuttingChecklistTemplate $stageTemplate,
    ): array {
        // Locked stages keep the checklist version they were captured against;
        // in-progress stages always show the currently active checklist.
        $locked = $stageModel !== null && $stageModel->isLocked();
        $baseTemplate = $locked
            ? ($stageTemplate ?? $currentTemplate)
            : ($currentTemplate ?? $stageTemplate);

        $resultsByName = $stageModel
            ? $stageModel->results->keyBy('checkpoint_name')
            : collect();

        $rows = [];
        $seen = [];

        foreach ($baseTemplate?->activeCheckpoints ?? collect() as $checkpoint) {
            $result = $resultsByName->get($checkpoint->name);

            $rows[] = $this->checkpointRow(
                $checkpoint->id,
                $checkpoint->name,
                $checkpoint->description,
                (bool) $checkpoint->mandatory_remarks_on_not_ok,
                (bool) $checkpoint->blocks_approval,
                $result,
            );
            $seen[] = $checkpoint->name;
        }

        // Keep any saved results whose checkpoint is no longer active (retired / older version).
        if ($stageModel) {
            foreach ($stageModel->results as $result) {
                if (in_array($result->checkpoint_name, $seen, true)) {
                    continue;
                }

                $rows[] = $this->checkpointRow(
                    $result->cutting_checkpoint_id,
                    $result->checkpoint_name,
                    $result->checkpoint?->description,
                    (bool) $result->checkpoint?->mandatory_remarks_on_not_ok,
                    (bool) $result->checkpoint?->blocks_approval,
                    $result,
                );
            }
        }

        return $rows;
    }

    protected function checkpointRow(
        ?int $checkpointId,
        string $name,
        ?string $description,
        bool $mandatoryRemarks,
        bool $blocksApproval,
        ?CuttingCheckpointResult $result,
    ): array {
        return [
            'checkpoint_id' => $checkpointId,
            'checkpoint_name' => $name,
            'description' => $description,
            'result' => $result?->result,
            'remarks' => $result?->remarks,
            'mandatory_remarks_on_not_ok' => $mandatoryRemarks,
            'blocks_approval' => $blocksApproval,
            'photos' => $result ? $result->photos->pluck('path')->all() : [],
        ];
    }

    protected function sizeRows(CuttingAudit $audit, string $stage): array
    {
        if ($stage !== 'cut_panel') {
            return [];
        }

        if ($audit->sizePatterns->isNotEmpty()) {
            return $audit->sizePatterns->map(fn ($p) => [
                'size_name' => $p->size_name,
                'top_result' => $p->top_result,
                'middle_result' => $p->middle_result,
                'bottom_result' => $p->bottom_result,
                'remarks' => $p->remarks,
            ])->all();
        }

        $count = max(1, (int) $audit->number_of_sizes);

        return collect(range(1, $count))->map(fn ($i) => [
            'size_name' => '',
            'top_result' => null,
            'middle_result' => null,
            'bottom_result' => null,
            'remarks' => null,
        ])->all();
    }

    protected function ensureStage(string $stage): void
    {
        if (! in_array($stage, CuttingAudit::STAGES, true)) {
            throw ValidationException::withMessages(['stage' => 'Unknown stage.']);
        }
    }
}
