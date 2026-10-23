<?php

namespace Tests\Feature\Cutting;

use App\Models\CuttingChecklistTemplate;
use App\Services\CuttingWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CuttingWorkflowTest extends TestCase
{
    use InteractsWithCutting;
    use RefreshDatabase;

    protected CuttingWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCutting();
        $this->workflow = app(CuttingWorkflowService::class);
    }

    public function test_later_stage_is_locked_until_previous_is_approved(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $audit = $this->makeAudit($qc);

        $this->expectException(ValidationException::class);

        $this->workflow->saveStage($audit, 'cut_panel', ['result' => 'approved'], $qc, true);
    }

    public function test_submitting_a_stage_auto_signs_the_submitter_and_awaits_next_signer(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $incharge = $this->userWithRole('cutting_incharge');
        $audit = $this->makeAudit($qc);

        $stage = $this->workflow->saveStage($audit, 'lay', [
            'result' => 'approved',
            'checkpoints' => [
                ['checkpoint_name' => 'Lay ends', 'result' => 'ok'],
                ['checkpoint_name' => 'Tension', 'result' => 'ok'],
            ],
        ], $qc, true);

        $this->assertSame('submitted', $stage->status);
        $this->assertNotNull($stage->locked_at);
        $this->assertDatabaseHas('cutting_signoffs', [
            'cutting_audit_id' => $audit->id,
            'stage' => 'lay',
            'role' => 'cutting_qc',
        ]);
        $this->assertSame('cutting_incharge', $this->workflow->nextExpectedRole($audit, 'lay'));
        $this->assertCount(2, $stage->results);

        // Locked: further edits are rejected.
        $this->expectException(ValidationException::class);
        $this->workflow->saveStage($audit, 'lay', ['result' => 'approved'], $qc, true);
    }

    public function test_all_signoffs_approve_the_stage_and_advance_the_audit(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $incharge = $this->userWithRole('cutting_incharge');
        $audit = $this->makeAudit($qc);

        $this->approveStage($audit, 'lay', $qc, $incharge);

        $stage = $audit->fresh()->stages()->where('stage', 'lay')->first();
        $this->assertSame('approved', $stage->status);
        $this->assertSame('cut_panel', $audit->fresh()->current_stage);
    }

    public function test_only_the_expected_role_may_sign_off(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $incharge = $this->userWithRole('cutting_incharge');
        $audit = $this->makeAudit($qc);

        $this->workflow->saveStage($audit, 'lay', ['result' => 'approved'], $qc, true);

        $this->expectException(ValidationException::class);

        // QC already signed; QC cannot sign again as the next (incharge) signer.
        $this->workflow->signOff($audit, 'lay', $qc);
    }

    public function test_blocking_checkpoint_prevents_approval(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $audit = $this->makeAudit($qc);

        $template = CuttingChecklistTemplate::currentFor('lay');
        $checkpoint = $template->checkpoints()->first();
        $checkpoint->update(['blocks_approval' => true]);

        $this->expectException(ValidationException::class);

        $this->workflow->saveStage($audit, 'lay', [
            'result' => 'approved',
            'checkpoints' => [
                ['checkpoint_id' => $checkpoint->id, 'checkpoint_name' => $checkpoint->name, 'result' => 'not_ok', 'remarks' => 'Issue'],
            ],
        ], $qc, true);
    }

    public function test_mandatory_remarks_required_when_marked_not_ok(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $audit = $this->makeAudit($qc);

        $template = CuttingChecklistTemplate::currentFor('lay');
        $checkpoint = $template->checkpoints()->first();
        $checkpoint->update(['mandatory_remarks_on_not_ok' => true]);

        $this->expectException(ValidationException::class);

        $this->workflow->saveStage($audit, 'lay', [
            'result' => 'not_approved',
            'checkpoints' => [
                ['checkpoint_id' => $checkpoint->id, 'checkpoint_name' => $checkpoint->name, 'result' => 'not_ok'],
            ],
        ], $qc, true);
    }

    public function test_special_approval_flow_requires_qam_decision_then_signoff(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $incharge = $this->userWithRole('cutting_incharge');
        $qam = $this->userWithRole('qam');
        $audit = $this->makeAudit($qc);

        $stage = $this->workflow->saveStage($audit, 'lay', [
            'result' => 'special_approval',
            'special_reason' => 'Minor shade variation approved by buyer.',
        ], $qc, true);

        $this->assertSame('special_pending', $stage->status);

        $approval = $audit->specialApprovals()->firstOrFail();
        $this->assertSame('pending', $approval->status);

        $this->workflow->signOff($audit, 'lay', $incharge);
        $this->assertSame('special_pending', $stage->fresh()->status);

        $this->workflow->decideSpecialApproval($approval, $qam, true, 'Accepted');

        $this->assertSame('special_approved', $stage->fresh()->status);
        $this->assertSame('cut_panel', $audit->fresh()->current_stage);
    }

    public function test_rejected_special_approval_marks_stage_not_approved(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $qam = $this->userWithRole('qam');
        $audit = $this->makeAudit($qc);

        $this->workflow->saveStage($audit, 'lay', [
            'result' => 'special_approval',
            'special_reason' => 'Attempted shortcut.',
        ], $qc, true);

        $approval = $audit->specialApprovals()->firstOrFail();
        $this->workflow->decideSpecialApproval($approval, $qam, false, 'Not acceptable');

        $stage = $audit->stages()->where('stage', 'lay')->first();
        $this->assertSame('not_approved', $stage->status);
        $this->assertSame('rejected', $approval->fresh()->status);
    }

    public function test_revision_creates_a_new_linked_audit(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $audit = $this->makeAudit($qc);

        $this->workflow->saveStage($audit, 'lay', ['result' => 'not_approved'], $qc, true);

        $revision = $this->workflow->requestRevision($audit, 'lay', $qc, 'Rework required');

        $this->assertSame($audit->id, $revision->parent_id);
        $this->assertSame($audit->id, $revision->root_id);
        $this->assertSame(2, $revision->revision);
        $this->assertSame('lay', $revision->current_stage);
        $this->assertNotSame($audit->audit_no, $revision->audit_no);
    }

    public function test_release_requires_bundling_approval_then_closes_the_audit(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $incharge = $this->userWithRole('cutting_incharge');
        $qam = $this->userWithRole('qam');
        $audit = $this->makeAudit($qc);

        $this->expectException(ValidationException::class);
        $this->workflow->releaseBundles($audit, $incharge);
    }

    public function test_full_workflow_release_sets_bundles_released_and_audit_closed(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $incharge = $this->userWithRole('cutting_incharge');
        $qam = $this->userWithRole('qam');
        $audit = $this->makeAudit($qc);

        $this->approveStage($audit, 'lay', $qc, $incharge);
        $this->approveStage($audit, 'cut_panel', $qc, $incharge);

        // Bundling: create a bundle while submitting.
        $this->workflow->saveStage($audit, 'bundling', [
            'result' => 'approved',
            'bundles' => [
                ['bundle_no' => 'B-001', 'size' => 'M', 'panel_numbers' => '1-10', 'pieces' => 10],
            ],
        ], $qc, true);
        $this->workflow->signOff($audit, 'bundling', $incharge);
        $this->workflow->signOff($audit, 'bundling', $qam);

        $this->assertSame('approved', $audit->stages()->where('stage', 'bundling')->first()->status);

        $released = $this->workflow->releaseBundles($audit, $incharge);

        $this->assertSame('closed', $released->status);
        $this->assertSame('closed', $released->current_stage);
        $this->assertNotNull($released->closed_at);
        $this->assertSame('released', $released->bundles()->first()->status);
    }

    public function test_signoff_is_allowed_for_any_role_holding_the_permission(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $manager = $this->userWithRole('manager');
        $audit = $this->makeAudit($qc);

        $this->workflow->saveStage($audit, 'lay', ['result' => 'approved'], $qc, true);

        // Manager is not a required sign-off role but holds the "signoff lay" permission.
        $stage = $this->workflow->signOff($audit, 'lay', $manager);

        $this->assertSame('approved', $stage->status);
        $this->assertDatabaseHas('cutting_signoffs', [
            'cutting_audit_id' => $audit->id,
            'stage' => 'lay',
            'user_id' => $manager->id,
        ]);
    }

    public function test_not_ok_checkpoint_requires_a_photo(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $audit = $this->makeAudit($qc);

        $this->expectException(ValidationException::class);

        $this->workflow->saveStage($audit, 'lay', [
            'result' => 'not_approved',
            'checkpoints' => [
                ['checkpoint_name' => 'Lay ends', 'result' => 'not_ok', 'remarks' => 'Uneven'],
            ],
        ], $qc, true);
    }

    public function test_not_ok_checkpoint_with_remarks_and_photo_is_accepted(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $audit = $this->makeAudit($qc);

        $stage = $this->workflow->saveStage($audit, 'lay', [
            'result' => 'not_approved',
            'checkpoints' => [
                ['checkpoint_name' => 'Lay ends', 'result' => 'not_ok', 'remarks' => 'Uneven', 'photo_paths' => ['cutting/checkpoints/test.png']],
            ],
        ], $qc, true);

        $this->assertSame('not_approved', $stage->status);
        $this->assertDatabaseHas('cutting_checkpoint_photos', ['path' => 'cutting/checkpoints/test.png']);
    }

    public function test_incomplete_bundle_row_is_rejected(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $incharge = $this->userWithRole('cutting_incharge');
        $audit = $this->makeAudit($qc);

        $this->approveStage($audit, 'lay', $qc, $incharge);
        $this->approveStage($audit, 'cut_panel', $qc, $incharge);

        $this->expectException(ValidationException::class);

        $this->workflow->saveStage($audit, 'bundling', [
            'result' => 'approved',
            'bundles' => [
                ['bundle_no' => 'B-1', 'size' => 'M', 'panel_numbers' => '1-10', 'pieces' => 0],
            ],
        ], $qc, true);
    }

    public function test_blank_bundle_rows_are_ignored(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $incharge = $this->userWithRole('cutting_incharge');
        $audit = $this->makeAudit($qc);

        $this->approveStage($audit, 'lay', $qc, $incharge);
        $this->approveStage($audit, 'cut_panel', $qc, $incharge);

        $this->workflow->saveStage($audit, 'bundling', [
            'result' => 'approved',
            'bundles' => [
                ['bundle_no' => '', 'size' => '', 'panel_numbers' => '', 'pieces' => ''],
                ['bundle_no' => 'B-1', 'size' => 'M', 'panel_numbers' => '1-10', 'pieces' => 10],
            ],
        ], $qc, true);

        $this->assertSame(1, $audit->bundles()->count());
    }
}
