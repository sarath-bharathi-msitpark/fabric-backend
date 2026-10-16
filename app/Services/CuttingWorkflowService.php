<?php

namespace App\Services;

use App\Models\CuttingAudit;
use App\Models\CuttingChecklistTemplate;
use App\Models\CuttingCheckpoint;
use App\Models\CuttingCheckpointResult;
use App\Models\CuttingSignoff;
use App\Models\CuttingSpecialApproval;
use App\Models\CuttingStage;
use App\Models\User;
use App\Notifications\CuttingSpecialApprovalRequested;
use App\Notifications\CuttingStageReadyNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CuttingWorkflowService
{
    public function requiredSignoffRoles(string $stage): array
    {
        $template = CuttingChecklistTemplate::currentFor($stage);

        return $template?->signoff_roles ?? [];
    }

    public function stageLabel(string $stage): string
    {
        return CuttingAudit::STAGE_LABELS[$stage] ?? ucfirst($stage);
    }

    public function isStageUnlocked(CuttingAudit $audit, string $stage): bool
    {
        $index = array_search($stage, CuttingAudit::STAGES, true);
        if ($index === false) {
            return false;
        }
        if ($index === 0) {
            return true;
        }

        $previous = $audit->stages()->where('stage', CuttingAudit::STAGES[$index - 1])->first();

        return $previous !== null
            && in_array($previous->status, ['approved', 'special_approved'], true);
    }

    public function nextExpectedRole(CuttingAudit $audit, string $stage): ?string
    {
        $stageModel = $audit->stages()->where('stage', $stage)->first();
        if (! $stageModel) {
            return null;
        }

        $signed = $stageModel->signoffs()->pluck('role')->all();

        foreach ($this->requiredSignoffRoles($stage) as $role) {
            if (! in_array($role, $signed, true)) {
                return $role;
            }
        }

        return null;
    }

    public function saveStage(CuttingAudit $audit, string $stage, array $payload, User $user, bool $submit = false): CuttingStage
    {
        if (! in_array($stage, CuttingAudit::STAGES, true)) {
            throw ValidationException::withMessages(['stage' => 'Invalid stage.']);
        }

        if (! $this->isStageUnlocked($audit, $stage)) {
            throw ValidationException::withMessages([
                'stage' => 'This stage is locked until the previous stage is approved.',
            ]);
        }

        $stageModel = $audit->stages()->firstOrCreate(['stage' => $stage], ['status' => 'draft']);

        if ($stageModel->isLocked()) {
            throw ValidationException::withMessages([
                'stage' => 'This stage has been submitted and is locked. Request a revision to make corrections.',
            ]);
        }

        $template = CuttingChecklistTemplate::currentFor($stage);

        return DB::transaction(function () use ($audit, $stage, $stageModel, $payload, $user, $submit, $template) {
            $this->syncCheckpointResults($stageModel, $payload['checkpoints'] ?? []);
            $this->syncSizePatterns($audit, $stage, $payload['size_patterns'] ?? []);
            $this->syncBundles($audit, $stage, $payload['bundles'] ?? []);

            $stageModel->comments = $payload['comments'] ?? $stageModel->comments;
            $stageModel->inspection_date = $payload['inspection_date'] ?? $stageModel->inspection_date ?? now();
            $stageModel->template_id = $template?->id;
            $stageModel->checklist_version = $template?->version;
            $stageModel->save();

            if (! $submit) {
                $audit->status = $audit->status === 'draft' ? 'in_progress' : $audit->status;
                $audit->save();

                return $stageModel->fresh();
            }

            return $this->submitStage($audit, $stageModel, $payload, $user);
        });
    }

    protected function syncCheckpointResults(CuttingStage $stage, array $rows): void
    {
        $existing = $stage->results()->get()->keyBy('checkpoint_name');
        $seen = [];

        foreach ($rows as $index => $row) {
            $name = $row['checkpoint_name'] ?? null;
            if (! $name) {
                continue;
            }
            $seen[] = $name;

            $result = $row['result'] ?? null;
            $remarks = $row['remarks'] ?? null;

            $checkpointId = $row['checkpoint_id'] ?? null;
            $checkpoint = $checkpointId
                ? CuttingCheckpoint::find($checkpointId)
                : null;

            $mandatory = $checkpoint
                ? (bool) $checkpoint->mandatory_remarks_on_not_ok
                : filter_var($row['mandatory_remarks_on_not_ok'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($result === 'not_ok' && $mandatory && blank($remarks)) {
                throw ValidationException::withMessages([
                    'checkpoints' => "Remarks are mandatory for '{$name}' when marked Not OK.",
                ]);
            }

            $model = $existing->get($name) ?? new CuttingCheckpointResult([
                'cutting_stage_id' => $stage->id,
                'checkpoint_name' => $name,
            ]);
            $model->cutting_checkpoint_id = $checkpointId ?? $model->cutting_checkpoint_id;
            $model->result = $result;
            $model->remarks = $remarks;
            $model->sort_order = $index + 1;
            $model->cutting_stage_id = $stage->id;
            $model->save();

            if (! empty($row['photo_paths'])) {
                foreach ((array) $row['photo_paths'] as $path) {
                    $model->photos()->firstOrCreate(['path' => $path]);
                }
            }
        }

        // Remove results that are no longer part of the checklist.
        $stage->results()->whereNotIn('checkpoint_name', $seen)->delete();
    }

    protected function syncSizePatterns(CuttingAudit $audit, string $stage, array $rows): void
    {
        if ($stage !== 'cut_panel' || empty($rows)) {
            return;
        }

        $audit->sizePatterns()->delete();
        foreach (array_values($rows) as $index => $row) {
            $audit->sizePatterns()->create([
                'size_name' => $row['size_name'] ?? ('Size '.($index + 1)),
                'sort_order' => $index + 1,
                'top_result' => $this->boolOrNull($row['top_result'] ?? null),
                'middle_result' => $this->boolOrNull($row['middle_result'] ?? null),
                'bottom_result' => $this->boolOrNull($row['bottom_result'] ?? null),
                'remarks' => $row['remarks'] ?? null,
            ]);
        }
    }

    protected function syncBundles(CuttingAudit $audit, string $stage, array $rows): void
    {
        if ($stage !== 'bundling' || empty($rows)) {
            return;
        }

        $audit->bundles()->where('status', 'created')->delete();
        foreach ($rows as $row) {
            if (blank($row['bundle_no'] ?? null)) {
                continue;
            }
            $audit->bundles()->create([
                'bundle_no' => $row['bundle_no'],
                'size' => $row['size'] ?? null,
                'panel_numbers' => $row['panel_numbers'] ?? null,
                'pieces' => (int) ($row['pieces'] ?? 0),
                'remarks' => $row['remarks'] ?? null,
                'status' => 'created',
            ]);
        }
    }

    protected function submitStage(CuttingAudit $audit, CuttingStage $stage, array $payload, User $user): CuttingStage
    {
        $stageKey = $stage->stage;
        $result = $payload['result'] ?? null;

        $allowed = array_keys(CuttingAudit::RESULTS[$stageKey] ?? []);
        if (! $result || ! in_array($result, $allowed, true)) {
            throw ValidationException::withMessages(['result' => 'Select an overall result before submitting.']);
        }

        // Approval-blocking checkpoints.
        $blocking = $stage->results()
            ->where('result', 'not_ok')
            ->get()
            ->filter(fn (CuttingCheckpointResult $r) => (bool) optional($r->checkpoint)->blocks_approval);

        if ($result === 'approved' && $blocking->isNotEmpty()) {
            throw ValidationException::withMessages([
                'result' => 'A checkpoint that blocks approval is marked Not OK. The stage cannot be approved.',
            ]);
        }

        $stage->result = $result;
        $stage->submitted_at = now();
        $stage->inspection_date = $stage->inspection_date ?? now();

        if ($result === 'not_approved') {
            $stage->status = 'not_approved';
        } elseif ($result === 'special_approval') {
            $stage->status = 'special_pending';
        } else {
            $stage->status = 'submitted';
        }

        $stage->locked_at = now();
        $stage->save();

        // Auto sign-off for the submitter when they hold a required role.
        $required = $this->requiredSignoffRoles($stageKey);
        $role = $this->roleForUser($user, $required);
        if ($role) {
            $this->recordSignoff($audit, $stage, $user, $role, $result, $payload['comments'] ?? null);
        }

        if ($result === 'special_approval') {
            $this->createSpecialApproval($audit, $stage, $user, $payload['special_reason'] ?? $payload['comments'] ?? null);
        }

        $stage = $this->resolveStage($audit, $stage);
        $this->notifyNextSigner($audit, $stage);

        return $stage->fresh();
    }

    public function signOff(CuttingAudit $audit, string $stage, User $user, ?string $comments = null, ?string $reason = null): CuttingStage
    {
        $stageModel = $audit->stages()->where('stage', $stage)->firstOrFail();

        if (! in_array($stageModel->status, ['submitted', 'special_pending'], true)) {
            throw ValidationException::withMessages(['signoff' => 'This stage is not awaiting sign-off.']);
        }

        $expected = $this->nextExpectedRole($audit, $stage);
        if (! $expected) {
            throw ValidationException::withMessages(['signoff' => 'All required sign-offs have been captured.']);
        }

        $isAdmin = $user->hasRole('admin');
        if (! $isAdmin && ! $user->hasRole($expected)) {
            throw ValidationException::withMessages([
                'signoff' => 'You are not the next authorized signer for this stage.',
            ]);
        }

        return DB::transaction(function () use ($audit, $stageModel, $user, $expected, $comments, $reason) {
            $this->recordSignoff($audit, $stageModel, $user, $expected, $stageModel->result, $comments, $reason);
            $stageModel = $this->resolveStage($audit, $stageModel);
            $this->notifyNextSigner($audit, $stageModel);

            return $stageModel->fresh();
        });
    }

    public function createSpecialApproval(CuttingAudit $audit, CuttingStage $stage, User $user, ?string $reason): CuttingSpecialApproval
    {
        if (blank($reason)) {
            throw ValidationException::withMessages([
                'special_reason' => 'A reason is required for special approval.',
            ]);
        }

        $approval = $audit->specialApprovals()->create([
            'cutting_stage_id' => $stage->id,
            'stage' => $stage->stage,
            'requested_by' => $user->id,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        $this->notifyQam($audit, $approval);

        return $approval;
    }

    public function decideSpecialApproval(CuttingSpecialApproval $approval, User $user, bool $approve, ?string $decisionReason = null): CuttingSpecialApproval
    {
        if ($approval->status !== 'pending') {
            throw ValidationException::withMessages(['approval' => 'This special approval has already been decided.']);
        }

        $approval->status = $approve ? 'approved' : 'rejected';
        $approval->approved_by = $user->id;
        $approval->approved_at = now();
        $approval->decision_reason = $decisionReason;
        $approval->save();

        $audit = $approval->audit;
        $stage = $approval->stage()->first();

        if ($stage) {
            if (! $approve) {
                $stage->status = 'not_approved';
                $stage->save();
            }
            $this->resolveStage($audit, $stage);
            $this->notifyNextSigner($audit, $stage->fresh());
        }

        return $approval->fresh();
    }

    protected function resolveStage(CuttingAudit $audit, CuttingStage $stage): CuttingStage
    {
        $required = $this->requiredSignoffRoles($stage->stage);
        $signed = $stage->signoffs()->pluck('role')->all();
        $allSigned = empty(array_diff($required, $signed));

        if ($stage->status === 'submitted' && $allSigned) {
            $stage->status = 'approved';
        }

        if ($stage->status === 'special_pending') {
            $approval = $audit->specialApprovals()
                ->where('stage', $stage->stage)
                ->latest()
                ->first();

            if ($approval && $approval->status === 'approved' && $allSigned) {
                $stage->status = 'special_approved';
            }
        }

        $stage->save();

        if (in_array($stage->status, ['approved', 'special_approved'], true)) {
            $this->advance($audit, $stage->stage);
        }

        return $stage->fresh();
    }

    protected function advance(CuttingAudit $audit, string $stage): void
    {
        $index = array_search($stage, CuttingAudit::STAGES, true);

        if ($index !== false && isset(CuttingAudit::STAGES[$index + 1])) {
            $audit->current_stage = CuttingAudit::STAGES[$index + 1];
            $audit->status = 'in_progress';
            $audit->save();
        }
    }

    public function requestRevision(CuttingAudit $audit, string $stage, User $user, ?string $reason): CuttingAudit
    {
        $stageModel = $audit->stages()->where('stage', $stage)->firstOrFail();

        if (! in_array($stageModel->status, ['not_approved', 'special_pending'], true)) {
            throw ValidationException::withMessages([
                'revision' => 'A revision can only be created for a rejected stage.',
            ]);
        }

        return DB::transaction(function () use ($audit, $stage, $user) {
            $new = $audit->replicate(['audit_no', 'status', 'current_stage', 'closed_at']);
            $new->audit_no = app(CuttingNumberService::class)->nextAuditNumber();
            $new->parent_id = $audit->id;
            $new->root_id = $audit->root_id ?? $audit->id;
            $new->revision = $audit->revision + 1;
            $new->status = 'in_progress';
            $new->current_stage = $stage;
            $new->created_by = $user->id;
            $new->save();

            // Carry over size rows so the QC does not re-enter them.
            foreach ($audit->sizePatterns as $pattern) {
                $new->sizePatterns()->create([
                    'size_name' => $pattern->size_name,
                    'sort_order' => $pattern->sort_order,
                    'remarks' => $pattern->remarks,
                ]);
            }

            return $new;
        });
    }

    public function releaseBundles(CuttingAudit $audit, User $user): CuttingAudit
    {
        $stage = $audit->stages()->where('stage', 'bundling')->first();

        if (! $stage || ! in_array($stage->status, ['approved', 'special_approved'], true)) {
            throw ValidationException::withMessages([
                'release' => 'Bundles can only be released after the bundling stage is approved.',
            ]);
        }

        if ($this->nextExpectedRole($audit, 'bundling') !== null) {
            throw ValidationException::withMessages([
                'release' => 'All required sign-offs must be completed before release.',
            ]);
        }

        return DB::transaction(function () use ($audit) {
            $audit->bundles()->update(['status' => 'released']);
            $audit->status = 'closed';
            $audit->current_stage = 'closed';
            $audit->closed_at = now();
            $audit->save();

            return $audit->fresh();
        });
    }

    protected function recordSignoff(
        CuttingAudit $audit,
        CuttingStage $stage,
        User $user,
        string $role,
        ?string $result,
        ?string $comments = null,
        ?string $reason = null,
    ): CuttingSignoff {
        return $audit->signoffs()->create([
            'cutting_stage_id' => $stage->id,
            'stage' => $stage->stage,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role' => $role,
            'result' => $result,
            'comments' => $comments,
            'reason' => $reason,
            'revision' => $audit->revision,
            'checklist_version' => $stage->checklist_version,
            'signed_at' => now(),
        ]);
    }

    protected function roleForUser(User $user, array $required): ?string
    {
        foreach ($required as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return null;
    }

    protected function notifyNextSigner(CuttingAudit $audit, CuttingStage $stage): void
    {
        if (! in_array($stage->status, ['submitted', 'special_pending'], true)) {
            return;
        }

        $expected = $this->nextExpectedRole($audit, $stage->stage);
        if (! $expected) {
            return;
        }

        foreach ($this->usersWithRole($expected) as $signer) {
            $this->safeNotify($signer, new CuttingStageReadyNotification($audit, $stage->stage, $expected));
        }
    }

    protected function notifyQam(CuttingAudit $audit, CuttingSpecialApproval $approval): void
    {
        $recipients = $this->usersWithRole('qam');
        if ($recipients->isEmpty()) {
            $recipients = $this->usersWithPermission('approve special approval');
        }

        foreach ($recipients as $qam) {
            $this->safeNotify($qam, new CuttingSpecialApprovalRequested($audit, $approval));
        }
    }

    protected function usersWithRole(string $role)
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', $role))
            ->get();
    }

    protected function usersWithPermission(string $permission)
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('permissions', fn ($q) => $q->where('name', $permission))
            ->get();
    }

    protected function safeNotify(User $user, $notification): void
    {
        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function boolOrNull($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
