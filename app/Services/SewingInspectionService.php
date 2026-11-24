<?php

namespace App\Services;

use App\Models\SewingDefectLog;
use App\Models\SewingDefectMaster;
use App\Models\SewingInspectionOperation;
use App\Models\SewingInspectionResult;
use App\Models\SewingInspectionRound;
use App\Models\SewingInspectionRule;
use App\Models\SewingInspectionSheet;
use App\Models\SewingSignoff;
use App\Models\User;
use App\Notifications\SewingAmberRedAlert;
use App\Notifications\SewingReauditEscalated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class SewingInspectionService
{
    /**
     * Ordered sign-off chain: QC -> Line Supervisor -> PM -> QAM.
     */
    public const SIGNOFF_ORDER = [
        SewingSignoff::ROLE_QC,
        SewingSignoff::ROLE_SUPERVISOR,
        SewingSignoff::ROLE_PM,
        SewingSignoff::ROLE_QAM,
    ];

    public const SIGNOFF_PERMISSIONS = [
        SewingSignoff::ROLE_QC => 'signoff sewing qc',
        SewingSignoff::ROLE_SUPERVISOR => 'signoff sewing supervisor',
        SewingSignoff::ROLE_PM => 'signoff sewing pm',
        SewingSignoff::ROLE_QAM => 'signoff sewing qam',
    ];

    public function __construct(
        protected SewingNumberService $numbers,
        protected SewingAuditLogger $audit,
    ) {}

    /**
     * Create an inspection sheet with its operation rows and round rows.
     *
     * @param  array<string, mixed>  $header
     * @param  array<int, array<string, mixed>>  $operations
     */
    public function createSheet(User $user, array $header, array $operations, ?int $rounds = null): SewingInspectionSheet
    {
        if (empty($operations)) {
            throw ValidationException::withMessages(['operations' => 'Add at least one operation row.']);
        }

        $rule = SewingInspectionRule::current();
        $rounds = $rounds ?: $rule->default_rounds;

        return DB::transaction(function () use ($user, $header, $operations, $rounds, $rule) {
            $sheet = SewingInspectionSheet::create(array_merge($header, [
                'sheet_no' => $this->numbers->nextSheetNumber(),
                'status' => SewingInspectionSheet::STATUS_DRAFT,
                'current_round' => 1,
                'pieces_per_audit' => $rule->pieces_per_audit,
                'created_by' => $user->id,
            ]));

            foreach (array_values($operations) as $index => $row) {
                $sheet->operations()->create([
                    'sewing_operation_id' => $row['sewing_operation_id'] ?? null,
                    'sewing_machine_id' => $row['sewing_machine_id'] ?? null,
                    'sewing_operator_id' => $row['sewing_operator_id'] ?? null,
                    'operation_name' => $row['operation_name'] ?? 'Operation '.($index + 1),
                    'machine_no' => $row['machine_no'] ?? null,
                    'sequence_no' => $row['sequence_no'] ?? ($index + 1),
                    'note' => $row['note'] ?? null,
                ]);
            }

            foreach (range(1, $rounds) as $roundNo) {
                $sheet->rounds()->create([
                    'round_no' => $roundNo,
                    'status' => SewingInspectionRound::STATUS_OPEN,
                    'started_at' => $roundNo === 1 ? now() : null,
                ]);
            }

            return $sheet->fresh(['operations', 'rounds']);
        });
    }

    /**
     * Record (or update) the 7-piece audit result for one operation in one round.
     */
    public function saveResult(
        User $user,
        SewingInspectionSheet $sheet,
        SewingInspectionOperation $operation,
        int $roundNo,
        int $defectCount,
        ?string $correctiveAction = null,
        ?string $note = null,
    ): SewingInspectionResult {
        $this->assertEditable($sheet);
        $this->assertOperationBelongs($sheet, $operation);
        $this->assertRoundEditable($sheet, $roundNo);

        $rule = SewingInspectionRule::current();
        $result = $rule->resultForCount($defectCount);

        $row = $sheet->results()->firstOrNew([
            'sewing_inspection_operation_id' => $operation->id,
            'round_no' => $roundNo,
        ]);

        $previousResult = $row->getOriginal('result');

        $row->defect_count = $defectCount;
        $row->result = $result;
        $row->corrective_action = $correctiveAction;
        $row->note = $note;
        $row->inspected_at = now();

        if ($result !== SewingInspectionResult::RESULT_RED) {
            $row->re_audit_count = null;
            $row->re_audit_result = null;
            $row->re_audit_note = null;
            $row->re_audited_at = null;
        }

        $row->save();

        if ($sheet->status === SewingInspectionSheet::STATUS_DRAFT) {
            $sheet->update(['status' => SewingInspectionSheet::STATUS_IN_PROGRESS]);
        }

        if ($row->requiresCorrectiveAction() && $previousResult !== $result) {
            $this->notifySupervisors($sheet, $row->fresh('operation'));
        }

        return $row;
    }

    /**
     * Log a defect with a full CAPA snapshot so past entries stay immutable (BR-12).
     */
    public function logDefect(
        User $user,
        SewingInspectionSheet $sheet,
        SewingInspectionOperation $operation,
        int $roundNo,
        int $defectMasterId,
        int $qty = 1,
        bool $isReaudit = false,
        ?string $note = null,
        ?string $photoPath = null,
    ): SewingDefectLog {
        $this->assertEditable($sheet);
        $this->assertOperationBelongs($sheet, $operation);
        $this->assertRoundEditable($sheet, $roundNo);

        $master = SewingDefectMaster::active()->findOrFail($defectMasterId);

        return $sheet->defectLogs()->create([
            'sewing_inspection_operation_id' => $operation->id,
            'round_no' => $roundNo,
            'is_reaudit' => $isReaudit,
            'sewing_defect_master_id' => $master->id,
            'defect_type' => $master->defect_type,
            'capa_code' => $master->capa_code,
            'defect_name' => $master->defect_name,
            'root_cause' => $master->root_cause,
            'corrective_action' => $master->corrective_action,
            'preventive_action' => $master->preventive_action,
            'qty' => max(1, $qty),
            'photo_path' => $photoPath,
            'note' => $note,
            'logged_by' => $user->id,
            'logged_at' => now(),
        ]);
    }

    /**
     * Remove a defect log entry before sign-off (FR-25). Only the current, open
     * round can be corrected; the removal is written to the audit trail.
     */
    public function removeDefect(User $user, SewingInspectionSheet $sheet, SewingDefectLog $log): void
    {
        $this->assertEditable($sheet);

        if ($log->sewing_inspection_sheet_id !== $sheet->id) {
            throw ValidationException::withMessages(['log' => 'This log entry does not belong to the sheet.']);
        }

        $this->assertRoundEditable($sheet, $log->round_no);

        DB::transaction(function () use ($user, $sheet, $log) {
            $this->audit->log(
                $sheet,
                'removed',
                'defect_log',
                json_encode([
                    'round_no' => $log->round_no,
                    'capa_code' => $log->capa_code,
                    'defect_name' => $log->defect_name,
                    'qty' => $log->qty,
                    'is_reaudit' => (bool) $log->is_reaudit,
                ]),
                null,
                $user->id,
            );

            $log->delete();
        });
    }

    /**
     * Complete the mandatory re-audit for a Red cell. Escalates to PM when still > Amber.
     */
    public function reAudit(
        User $user,
        SewingInspectionSheet $sheet,
        SewingInspectionResult $result,
        int $defectCount,
        ?string $note = null,
    ): SewingInspectionResult {
        $this->assertEditable($sheet);

        if ($result->sewing_inspection_sheet_id !== $sheet->id) {
            throw ValidationException::withMessages(['result' => 'This cell does not belong to the sheet.']);
        }

        if (! $result->isRed()) {
            throw ValidationException::withMessages(['result' => 'Re-audit is only required for Red cells.']);
        }

        $rule = SewingInspectionRule::current();
        $cleared = $defectCount <= $rule->amber_max;

        $result->update([
            're_audit_count' => $defectCount,
            're_audit_result' => $cleared
                ? SewingInspectionResult::REAUDIT_CLEARED
                : SewingInspectionResult::REAUDIT_ESCALATED,
            're_audit_note' => $note,
            're_audited_at' => now(),
        ]);

        if (! $cleared) {
            $this->notifyEscalation($sheet, $result->fresh('operation'));
        }

        return $result;
    }

    /**
     * Close the current round. Blocks when any cell is unfilled or a Red cell is
     * still awaiting its mandatory re-audit (BR-09), then opens the next round.
     */
    public function closeRound(User $user, SewingInspectionSheet $sheet, int $roundNo): void
    {
        $this->assertEditable($sheet);

        if ($roundNo !== $sheet->current_round) {
            throw ValidationException::withMessages(['round' => 'Only the current round can be closed.']);
        }

        $round = $sheet->rounds()->where('round_no', $roundNo)->firstOrFail();

        if ($round->status === SewingInspectionRound::STATUS_CLOSED) {
            throw ValidationException::withMessages(['round' => 'This round is already closed.']);
        }

        $operations = $sheet->operations()->get();
        $results = $sheet->results()->where('round_no', $roundNo)->get()->keyBy('sewing_inspection_operation_id');

        $missing = $operations->filter(fn ($op) => ! $results->has($op->id));

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'round' => 'Every operation needs a result before the round can be closed ('.$missing->count().' missing).',
            ]);
        }

        $openRed = $results->first(fn (SewingInspectionResult $r) => $r->isRed() && $r->re_audit_result === null);

        if ($openRed) {
            throw ValidationException::withMessages([
                'round' => 'A Red cell still needs its mandatory re-audit before this round can be closed.',
            ]);
        }

        DB::transaction(function () use ($sheet, $round, $roundNo) {
            $round->update(['status' => SewingInspectionRound::STATUS_CLOSED, 'closed_at' => now()]);

            $next = $sheet->rounds()->where('round_no', $roundNo + 1)->first();

            if ($next) {
                $next->update(['started_at' => $next->started_at ?? now()]);
                $sheet->update(['current_round' => $roundNo + 1]);
            }
        });
    }

    /**
     * Record one sign-off in the QC -> Supervisor -> PM -> QAM chain. QAM locks the sheet.
     */
    public function signOff(User $user, SewingInspectionSheet $sheet, string $role, ?string $comments = null, ?string $reason = null): SewingSignoff
    {
        if (! array_key_exists($role, SewingSignoff::ROLES)) {
            throw ValidationException::withMessages(['role' => 'Unknown sign-off role.']);
        }

        if ($sheet->isLocked()) {
            throw ValidationException::withMessages(['role' => 'This sheet is locked.']);
        }

        if (! $user->can(self::SIGNOFF_PERMISSIONS[$role])) {
            abort(403);
        }

        $index = array_search($role, self::SIGNOFF_ORDER, true);
        $expected = self::SIGNOFF_ORDER[$index];
        $signedRoles = $sheet->signoffs()->pluck('role')->all();

        if (in_array($role, $signedRoles, true)) {
            throw ValidationException::withMessages(['role' => 'This role has already signed off.']);
        }

        $priorRoles = array_slice(self::SIGNOFF_ORDER, 0, $index);

        foreach ($priorRoles as $prior) {
            if (! in_array($prior, $signedRoles, true)) {
                throw ValidationException::withMessages([
                    'role' => 'Sign-offs must be completed in order: '.implode(' -> ', array_map(fn ($r) => SewingSignoff::ROLES[$r], self::SIGNOFF_ORDER)).'.',
                ]);
            }
        }

        return DB::transaction(function () use ($user, $sheet, $role, $comments, $reason) {
            $signoff = $sheet->signoffs()->create([
                'role' => $role,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'comments' => $comments,
                'reason' => $reason,
                'signed_at' => now(),
            ]);

            if ($role === SewingSignoff::ROLE_QAM) {
                $sheet->update([
                    'status' => SewingInspectionSheet::STATUS_LOCKED,
                    'locked_at' => now(),
                ]);
            } elseif ($sheet->status === SewingInspectionSheet::STATUS_DRAFT) {
                $sheet->update(['status' => SewingInspectionSheet::STATUS_IN_PROGRESS]);
            }

            return $signoff;
        });
    }

    /**
     * Apply a controlled correction to a locked sheet, capturing a reason for every field.
     *
     * @param  array<string, mixed>  $changes
     */
    public function correctAfterLock(User $user, SewingInspectionSheet $sheet, array $changes, string $reason): void
    {
        if (! $sheet->isLocked()) {
            throw ValidationException::withMessages(['sheet' => 'Only a locked sheet can be corrected this way.']);
        }

        $allowed = [
            'style_name', 'color', 'po_number', 'lot_number', 'bundle_no', 'shift', 'inspection_date', 'qc_id',
        ];

        $dirty = array_intersect_key($changes, array_flip($allowed));

        if (empty($dirty)) {
            throw ValidationException::withMessages(['sheet' => 'No correctable fields were provided.']);
        }

        DB::transaction(function () use ($user, $sheet, $dirty, $reason) {
            foreach ($dirty as $field => $new) {
                $old = $sheet->getAttribute($field);

                if ((string) $old === (string) $new) {
                    continue;
                }

                $this->audit->log($sheet, 'corrected', $field, $old, $new, $reason, $user->id);
            }

            $sheet->update($dirty);
        });
    }

    protected function notifyEscalation(SewingInspectionSheet $sheet, SewingInspectionResult $result): void
    {
        $pms = User::role('production_manager')->get();

        if ($pms->isNotEmpty()) {
            Notification::send($pms, new SewingReauditEscalated($sheet, $result));
        }
    }

    /**
     * Alert the Line Supervisor when a cell first lands Amber or Red (FR-10).
     */
    protected function notifySupervisors(SewingInspectionSheet $sheet, SewingInspectionResult $result): void
    {
        $supervisors = User::role('sewing_supervisor')->get();

        if ($supervisors->isNotEmpty()) {
            Notification::send($supervisors, new SewingAmberRedAlert($sheet, $result));
        }
    }

    protected function assertEditable(SewingInspectionSheet $sheet): void
    {
        if ($sheet->isLocked()) {
            throw ValidationException::withMessages(['sheet' => 'This sheet is locked and cannot be edited.']);
        }
    }

    protected function assertOperationBelongs(SewingInspectionSheet $sheet, SewingInspectionOperation $operation): void
    {
        if ($operation->sewing_inspection_sheet_id !== $sheet->id) {
            throw ValidationException::withMessages(['operation' => 'This operation does not belong to the sheet.']);
        }
    }

    protected function assertRoundEditable(SewingInspectionSheet $sheet, int $roundNo): void
    {
        if ($roundNo !== $sheet->current_round) {
            throw ValidationException::withMessages(['round' => 'Only the current round can be edited.']);
        }

        $round = $sheet->rounds()->where('round_no', $roundNo)->first();

        if ($round && $round->status === SewingInspectionRound::STATUS_CLOSED) {
            throw ValidationException::withMessages(['round' => 'This round is closed.']);
        }
    }
}
