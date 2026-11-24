<?php

namespace Tests\Feature\Sewing;

use App\Http\Controllers\Admin\Sewing\SewingReportController;
use App\Models\SewingDefectMaster;
use App\Models\SewingInspectionSheet;
use App\Models\User;
use App\Services\SewingInspectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SewingPhase5Test extends TestCase
{
    use InteractsWithSewing, RefreshDatabase;

    protected function service(): SewingInspectionService
    {
        return app(SewingInspectionService::class);
    }

    protected function newSheet(User $user): SewingInspectionSheet
    {
        return $this->service()->createSheet($user, [
            'inspection_date' => now()->toDateString(),
        ], [
            ['operation_name' => 'Attach collar'],
        ], 4);
    }

    public function test_dashboard_surfaces_red_metrics(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);

        $this->service()->saveResult($qc, $sheet, $sheet->operations->first(), 1, 5);

        $this->actingAs($qc)
            ->get(route('admin.sewing.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['red'] === 1 && $s['open_reaudits'] === 1 && $s['total'] === 1);
    }

    public function test_live_board_data_lists_the_open_sheet(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);

        $this->actingAs($qc)
            ->get(route('admin.sewing.live-board.data'))
            ->assertOk()
            ->assertJsonStructure(['lines', 'generated_at'])
            ->assertJsonPath('lines.0.sheets.0.sheet_no', $sheet->sheet_no);
    }

    public function test_report_pages_render_for_each_type(): void
    {
        $this->seedSewing();
        $pm = $this->userWithRole('production_manager');

        $this->actingAs($pm)->get(route('admin.sewing.reports.index'))->assertOk();

        foreach (array_keys(SewingReportController::TYPES) as $type) {
            $this->actingAs($pm)->get(route('admin.sewing.reports.show', $type))->assertOk();
        }
    }

    public function test_reports_export_to_excel_and_pdf(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $pm = $this->userWithRole('production_manager');
        $sheet = $this->newSheet($qc);

        $master = SewingDefectMaster::active()->firstOrFail();
        $this->service()->logDefect($qc, $sheet, $sheet->operations->first(), 1, $master->id, 2);

        $this->actingAs($pm)
            ->get(route('admin.sewing.reports.export', ['type' => 'defect-analysis', 'format' => 'xlsx']))
            ->assertOk()
            ->assertDownload();

        $this->actingAs($pm)
            ->get(route('admin.sewing.reports.export', ['type' => 'defect-analysis', 'format' => 'pdf']))
            ->assertOk()
            ->assertDownload();
    }

    public function test_repeat_red_page_renders(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $pm = $this->userWithRole('production_manager');
        $sheet = $this->newSheet($qc);

        $this->service()->saveResult($qc, $sheet, $sheet->operations->first(), 1, 6);

        $this->actingAs($pm)->get(route('admin.sewing.repeat-red.index'))->assertOk();
    }

    public function test_locked_sheet_can_be_corrected_with_a_reason(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $qam = $this->userWithRole('qam');
        $sheet = $this->newSheet($qc);
        $sheet->update(['status' => SewingInspectionSheet::STATUS_LOCKED, 'locked_at' => now()]);

        $this->actingAs($qam)->post(route('admin.sewing.sheets.correct', $sheet), [
            'po_number' => 'PO-CORRECTED',
            'reason' => 'Typo in PO captured by QC',
        ])->assertSessionHasNoErrors();

        $this->assertSame('PO-CORRECTED', $sheet->fresh()->po_number);
        $this->assertDatabaseHas('sewing_audit_logs', [
            'auditable_id' => $sheet->id,
            'action' => 'corrected',
            'field' => 'po_number',
            'new_value' => 'PO-CORRECTED',
        ]);
    }

    public function test_non_privileged_user_cannot_correct_a_locked_sheet(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);
        $sheet->update(['status' => SewingInspectionSheet::STATUS_LOCKED, 'locked_at' => now()]);

        $this->actingAs($qc)->post(route('admin.sewing.sheets.correct', $sheet), [
            'po_number' => 'NOPE',
            'reason' => 'should be blocked',
        ])->assertForbidden();
    }

    public function test_sheet_print_pdf_and_excel_render(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $pm = $this->userWithRole('production_manager');
        $sheet = $this->newSheet($qc);
        $this->service()->saveResult($qc, $sheet, $sheet->operations->first(), 1, 1);

        $this->actingAs($qc)->get(route('admin.sewing.sheets.print', $sheet))->assertOk()->assertSee($sheet->sheet_no);
        $this->actingAs($qc)->get(route('admin.sewing.sheets.pdf', $sheet))->assertOk()->assertDownload();
        $this->actingAs($pm)->get(route('admin.sewing.sheets.export', $sheet))->assertOk()->assertDownload();
    }

    public function test_sheet_excel_export_requires_export_permission(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);

        $this->actingAs($qc)->get(route('admin.sewing.sheets.export', $sheet))->assertForbidden();
    }

    public function test_grid_renders_br09_red_warning_hooks(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);

        $this->actingAs($qc)
            ->get(route('admin.sewing.grid.show', $sheet))
            ->assertOk()
            ->assertSee('result-form')
            ->assertSee('data-amber-max', false);
    }
}
