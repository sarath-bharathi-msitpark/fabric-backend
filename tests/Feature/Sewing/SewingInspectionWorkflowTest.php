<?php

namespace Tests\Feature\Sewing;

use App\Models\SewingDefectMaster;
use App\Models\SewingInspectionResult;
use App\Models\SewingInspectionSheet;
use App\Models\User;
use App\Notifications\SewingReauditEscalated;
use App\Services\SewingInspectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SewingInspectionWorkflowTest extends TestCase
{
    use InteractsWithSewing, RefreshDatabase;

    protected function service(): SewingInspectionService
    {
        return app(SewingInspectionService::class);
    }

    protected function newSheet(User $user, int $operationCount = 1, ?int $rounds = 4): SewingInspectionSheet
    {
        $operations = [];
        for ($i = 1; $i <= $operationCount; $i++) {
            $operations[] = ['operation_name' => 'Operation '.$i];
        }

        return $this->service()->createSheet($user, [
            'inspection_date' => now()->toDateString(),
        ], $operations, $rounds);
    }

    public function test_store_creates_sheet_with_operations_and_rounds(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');

        $response = $this->actingAs($qc)->post(route('admin.sewing.sheets.store'), [
            'inspection_date' => now()->toDateString(),
            'operations' => [
                ['operation_name' => 'Attach collar'],
                ['operation_name' => 'Attach sleeve'],
            ],
            'rounds' => 3,
        ]);

        $sheet = SewingInspectionSheet::firstOrFail();
        $response->assertRedirect(route('admin.sewing.grid.show', $sheet));

        $this->assertCount(2, $sheet->operations);
        $this->assertCount(3, $sheet->rounds);
        $this->assertSame(1, $sheet->current_round);
        $this->assertStringStartsWith('SII-', $sheet->sheet_no);
    }

    public function test_save_result_computes_green_amber_red_band(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);
        $operation = $sheet->operations->first();

        $this->actingAs($qc)->post(route('admin.sewing.grid.result', [$sheet, $operation]), [
            'round_no' => 1,
            'defect_count' => 0,
        ])->assertSessionHasNoErrors();

        $this->actingAs($qc)->post(route('admin.sewing.grid.result', [$sheet, $operation]), [
            'round_no' => 1,
            'defect_count' => 2,
        ]);

        $result = $sheet->results()->where('round_no', 1)->firstOrFail();
        $this->assertSame(SewingInspectionResult::RESULT_AMBER, $result->result);

        $this->actingAs($qc)->post(route('admin.sewing.grid.result', [$sheet, $operation]), [
            'round_no' => 1,
            'defect_count' => 5,
        ]);

        $this->assertSame(SewingInspectionResult::RESULT_RED, $result->fresh()->result);
    }

    public function test_log_defect_snapshots_capa_fields(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);
        $operation = $sheet->operations->first();
        $master = SewingDefectMaster::active()->firstOrFail();

        $this->actingAs($qc)->post(route('admin.sewing.grid.defect', [$sheet, $operation]), [
            'round_no' => 1,
            'defect_master_id' => $master->id,
            'qty' => 3,
        ])->assertSessionHasNoErrors();

        $log = $sheet->defectLogs()->firstOrFail();
        $this->assertSame($master->capa_code, $log->capa_code);
        $this->assertSame($master->defect_name, $log->defect_name);
        $this->assertSame($master->root_cause, $log->root_cause);
        $this->assertSame(3, $log->qty);
    }

    public function test_reaudit_clears_when_within_amber_band(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);
        $operation = $sheet->operations->first();

        $this->service()->saveResult($qc, $sheet, $operation, 1, 6);
        $result = $sheet->results()->where('round_no', 1)->firstOrFail();

        $this->actingAs($qc)->post(route('admin.sewing.grid.reaudit', [$sheet, $result]), [
            'defect_count' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame(SewingInspectionResult::REAUDIT_CLEARED, $result->fresh()->re_audit_result);
    }

    public function test_reaudit_escalates_and_notifies_pm(): void
    {
        $this->seedSewing();
        Notification::fake();

        $qc = $this->userWithRole('sewing_qc');
        $pm = $this->userWithRole('production_manager');
        $sheet = $this->newSheet($qc);
        $operation = $sheet->operations->first();

        $this->service()->saveResult($qc, $sheet, $operation, 1, 6);
        $result = $sheet->results()->where('round_no', 1)->firstOrFail();

        $this->service()->reAudit($qc, $sheet, $result, 5);

        $this->assertSame(SewingInspectionResult::REAUDIT_ESCALATED, $result->fresh()->re_audit_result);
        Notification::assertSentTo($pm, SewingReauditEscalated::class);
    }

    public function test_close_round_blocks_when_a_result_is_missing(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc, 2);

        $this->actingAs($qc)->post(route('admin.sewing.grid.close-round', [$sheet, 1]))
            ->assertSessionHasErrors('round');

        $this->assertSame(1, $sheet->fresh()->current_round);
    }

    public function test_close_round_blocks_until_red_is_reaudited_then_advances(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);
        $operation = $sheet->operations->first();

        $this->service()->saveResult($qc, $sheet, $operation, 1, 6);
        $result = $sheet->results()->where('round_no', 1)->firstOrFail();

        $this->actingAs($qc)->post(route('admin.sewing.grid.close-round', [$sheet, 1]))
            ->assertSessionHasErrors('round');

        $this->service()->reAudit($qc, $sheet, $result, 1);

        $this->actingAs($qc)->post(route('admin.sewing.grid.close-round', [$sheet, 1]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $sheet->fresh()->current_round);
    }

    public function test_signoff_must_follow_order_and_qam_locks_sheet(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $supervisor = $this->userWithRole('sewing_supervisor');
        $pm = $this->userWithRole('production_manager');
        $qam = $this->userWithRole('qam');
        $sheet = $this->newSheet($qc);

        $this->actingAs($pm)->post(route('admin.sewing.sheets.signoff', $sheet), ['role' => 'pm'])
            ->assertSessionHasErrors('role');

        $this->actingAs($qc)->post(route('admin.sewing.sheets.signoff', $sheet), ['role' => 'qc'])
            ->assertSessionHasNoErrors();
        $this->actingAs($supervisor)->post(route('admin.sewing.sheets.signoff', $sheet), ['role' => 'supervisor'])
            ->assertSessionHasNoErrors();
        $this->actingAs($pm)->post(route('admin.sewing.sheets.signoff', $sheet), ['role' => 'pm'])
            ->assertSessionHasNoErrors();
        $this->actingAs($qam)->post(route('admin.sewing.sheets.signoff', $sheet), ['role' => 'qam'])
            ->assertSessionHasNoErrors();

        $sheet->refresh();
        $this->assertTrue($sheet->isLocked());
        $this->assertSame(SewingInspectionSheet::STATUS_LOCKED, $sheet->status);
        $this->assertCount(4, $sheet->signoffs);
    }

    public function test_locked_sheet_rejects_further_capture(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $supervisor = $this->userWithRole('sewing_supervisor');
        $pm = $this->userWithRole('production_manager');
        $qam = $this->userWithRole('qam');
        $sheet = $this->newSheet($qc);
        $operation = $sheet->operations->first();

        foreach (['qc' => $qc, 'supervisor' => $supervisor, 'pm' => $pm, 'qam' => $qam] as $role => $user) {
            $this->actingAs($user)->post(route('admin.sewing.sheets.signoff', $sheet), ['role' => $role]);
        }

        $this->actingAs($qc)->post(route('admin.sewing.grid.result', [$sheet, $operation]), [
            'round_no' => 1,
            'defect_count' => 0,
        ])->assertSessionHasErrors('sheet');

        $this->assertSame(0, $sheet->fresh()->results()->count());
    }
}
