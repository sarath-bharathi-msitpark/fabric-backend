<?php

namespace Tests\Feature\Sewing;

use App\Http\Controllers\Admin\Sewing\SewingReportController;
use App\Models\SewingDefectMaster;
use App\Models\SewingInspectionSheet;
use App\Models\SewingOperation;
use App\Models\SewingOperator;
use App\Models\SewingStyleOperation;
use App\Models\Style;
use App\Models\User;
use App\Services\SewingDashboardService;
use App\Services\SewingInspectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
        $this->service()->saveResult($qc, $sheet, $sheet->operations->first(), 1, 2);
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

    public function test_supervisor_is_alerted_once_on_amber_or_red(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $supervisor = $this->userWithRole('sewing_supervisor');
        $sheet = $this->newSheet($qc);

        $this->service()->saveResult($qc, $sheet, $sheet->operations->first(), 1, 2);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $supervisor->id,
            'notifiable_type' => User::class,
        ]);

        $this->service()->saveResult($qc, $sheet, $sheet->operations->first(), 1, 2);

        $this->assertSame(1, $supervisor->notifications()->count());
    }

    public function test_defect_log_entry_can_be_removed_before_signoff(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);
        $master = SewingDefectMaster::active()->firstOrFail();
        $this->service()->saveResult($qc, $sheet, $sheet->operations->first(), 1, 2);

        $log = $this->service()->logDefect($qc, $sheet, $sheet->operations->first(), 1, $master->id, 2);

        $this->actingAs($qc)
            ->delete(route('admin.sewing.grid.defect.remove', [$sheet, $log]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sewing_defect_logs', ['id' => $log->id]);
        $this->assertDatabaseHas('sewing_audit_logs', [
            'auditable_id' => $sheet->id,
            'action' => 'removed',
            'field' => 'defect_log',
        ]);
    }

    public function test_defect_log_entry_cannot_be_removed_after_lock(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);
        $master = SewingDefectMaster::active()->firstOrFail();
        $this->service()->saveResult($qc, $sheet, $sheet->operations->first(), 1, 2);

        $log = $this->service()->logDefect($qc, $sheet, $sheet->operations->first(), 1, $master->id, 2);
        $sheet->update(['status' => SewingInspectionSheet::STATUS_LOCKED, 'locked_at' => now()]);

        $this->actingAs($qc)
            ->delete(route('admin.sewing.grid.defect.remove', [$sheet, $log]))
            ->assertSessionHasErrors('sheet');

        $this->assertDatabaseHas('sewing_defect_logs', ['id' => $log->id]);
    }

    public function test_grid_shows_logged_versus_required_defects(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);
        $master = SewingDefectMaster::active()->firstOrFail();

        $this->service()->saveResult($qc, $sheet, $sheet->operations->first(), 1, 3);
        $this->service()->logDefect($qc, $sheet, $sheet->operations->first(), 1, $master->id, 2);

        $this->actingAs($qc)
            ->get(route('admin.sewing.grid.show', $sheet))
            ->assertOk()
            ->assertSee('Logged 2 of 3');
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

    public function test_defect_count_cannot_exceed_pieces_per_audit(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);
        $op = $sheet->operations->first();

        $this->actingAs($qc)->post(route('admin.sewing.grid.result', [$sheet, $op]), [
            'round_no' => 1,
            'defect_count' => 8,
        ])->assertSessionHasErrors('defect_count');
    }

    public function test_logged_quantity_cannot_exceed_the_defect_count(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);
        $op = $sheet->operations->first();
        $master = SewingDefectMaster::active()->firstOrFail();

        $this->service()->saveResult($qc, $sheet, $op, 1, 2);

        $this->actingAs($qc)->post(route('admin.sewing.grid.defect', [$sheet, $op]), [
            'round_no' => 1,
            'defect_master_id' => $master->id,
            'qty' => 3,
        ])->assertSessionHasErrors('qty');
    }

    public function test_close_round_blocks_until_every_piece_is_logged(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);
        $op = $sheet->operations->first();
        $master = SewingDefectMaster::active()->firstOrFail();

        $this->service()->saveResult($qc, $sheet, $op, 1, 2);

        $this->actingAs($qc)->post(route('admin.sewing.grid.close-round', [$sheet, 1]))
            ->assertSessionHasErrors('round');

        $this->service()->logDefect($qc, $sheet, $op, 1, $master->id, 2);

        $this->actingAs($qc)->post(route('admin.sewing.grid.close-round', [$sheet, 1]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $sheet->fresh()->current_round);
    }

    public function test_grid_shows_round_progress_header(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);

        $this->actingAs($qc)
            ->get(route('admin.sewing.grid.show', $sheet))
            ->assertOk()
            ->assertSee('Round 1 progress')
            ->assertSee('0/1');
    }

    public function test_grid_renders_the_number_pad(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);

        $this->actingAs($qc)
            ->get(route('admin.sewing.grid.show', $sheet))
            ->assertOk()
            ->assertSee('num-pad')
            ->assertSee('num-pad-btn', false);
    }

    public function test_register_filters_by_line_and_result(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $lineA = $this->makeLine('LA');
        $lineB = $this->makeLine('LB');

        $sheetA = $this->service()->createSheet($qc, [
            'inspection_date' => now()->toDateString(),
            'sewing_line_id' => $lineA->id,
        ], [['operation_name' => 'Op A']], 4);

        $sheetB = $this->service()->createSheet($qc, [
            'inspection_date' => now()->toDateString(),
            'sewing_line_id' => $lineB->id,
        ], [['operation_name' => 'Op B']], 4);

        $this->service()->saveResult($qc, $sheetA, $sheetA->operations->first(), 1, 5);

        $this->actingAs($qc)
            ->get(route('admin.sewing.sheets.index', ['sewing_line_id' => $lineA->id]))
            ->assertOk()
            ->assertSee($sheetA->sheet_no)
            ->assertDontSee($sheetB->sheet_no);

        $this->actingAs($qc)
            ->get(route('admin.sewing.sheets.index', ['result' => 'red']))
            ->assertOk()
            ->assertSee($sheetA->sheet_no)
            ->assertDontSee($sheetB->sheet_no);
    }

    public function test_defect_log_shows_summary_and_exports(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $pm = $this->userWithRole('production_manager');
        $sheet = $this->newSheet($qc);
        $master = SewingDefectMaster::active()->firstOrFail();

        $this->service()->saveResult($qc, $sheet, $sheet->operations->first(), 1, 2);
        $this->service()->logDefect($qc, $sheet, $sheet->operations->first(), 1, $master->id, 2);

        $this->actingAs($qc)
            ->get(route('admin.sewing.defect-log.index'))
            ->assertOk()
            ->assertSee($master->capa_code);

        $this->actingAs($pm)
            ->get(route('admin.sewing.defect-log.export', ['format' => 'xlsx']))
            ->assertOk()
            ->assertDownload();

        $this->actingAs($pm)
            ->get(route('admin.sewing.defect-log.export', ['format' => 'pdf']))
            ->assertOk()
            ->assertDownload();

        $this->actingAs($qc)
            ->get(route('admin.sewing.defect-log.export', ['format' => 'xlsx']))
            ->assertForbidden();
    }

    public function test_dcl_import_creates_rows_and_reports_errors(): void
    {
        $this->seedSewing();
        $qam = $this->userWithRole('qam');

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Type', 'CAPA Code', 'Defect Name', 'Root Cause', 'Corrective Action', 'Preventive Action'],
            ['sewing', 'S99', 'Imported defect', 'cause', 'fix', 'prevent'],
            ['bogus', 'X1', 'Bad type row', '', '', ''],
        ], null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'dcl').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $file = new UploadedFile($path, 'dcl.xlsx', null, null, true);

        $this->actingAs($qam)
            ->post(route('admin.sewing.settings.defects.import'), ['file' => $file])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sewing_defect_masters', [
            'capa_code' => 'S99',
            'defect_name' => 'Imported defect',
            'defect_type' => 'sewing',
        ]);
        $this->assertDatabaseMissing('sewing_defect_masters', ['capa_code' => 'X1']);
    }

    public function test_dcl_template_download_and_import_permission(): void
    {
        $this->seedSewing();
        $qam = $this->userWithRole('qam');
        $qc = $this->userWithRole('sewing_qc');

        $this->actingAs($qam)
            ->get(route('admin.sewing.settings.defects.template'))
            ->assertOk()
            ->assertDownload();

        $this->actingAs($qc)
            ->get(route('admin.sewing.settings.defects.template'))
            ->assertForbidden();
    }

    protected function makeStyle(): Style
    {
        return Style::create([
            'style_number' => 'STY-'.uniqid(),
            'buyer_id' => $this->makeBuyer()->id,
            'order_quantity' => 100,
            'target_date' => now()->addDays(10)->toDateString(),
            'status' => 'planning',
        ]);
    }

    public function test_style_operations_can_be_managed_and_reordered(): void
    {
        $this->seedSewing();
        $qam = $this->userWithRole('qam');
        $style = $this->makeStyle();
        $opA = SewingOperation::create(['name' => 'Op A', 'is_active' => true]);
        $opB = SewingOperation::create(['name' => 'Op B', 'is_active' => true]);

        $this->actingAs($qam)->post(route('admin.sewing.settings.style-operations.store'), [
            'style_id' => $style->id,
            'sewing_operation_id' => $opA->id,
        ])->assertSessionHasNoErrors();

        $this->actingAs($qam)->post(route('admin.sewing.settings.style-operations.store'), [
            'style_id' => $style->id,
            'sewing_operation_id' => $opB->id,
        ])->assertSessionHasNoErrors();

        $second = SewingStyleOperation::where('style_id', $style->id)
            ->where('sewing_operation_id', $opB->id)->firstOrFail();

        $this->actingAs($qam)
            ->post(route('admin.sewing.settings.style-operations.move', $second), ['direction' => 'up'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $second->fresh()->sequence_no);
        $this->assertSame(2, SewingStyleOperation::where('style_id', $style->id)
            ->where('sewing_operation_id', $opA->id)->first()->sequence_no);
    }

    public function test_create_sheet_loads_the_styles_operations(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $style = $this->makeStyle();
        $op = SewingOperation::create(['name' => 'Op A', 'is_active' => true]);

        SewingStyleOperation::create([
            'style_id' => $style->id,
            'sewing_operation_id' => $op->id,
            'sequence_no' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($qc)
            ->get(route('admin.sewing.sheets.create'))
            ->assertOk()
            ->assertViewHas('styleOperations', fn ($map) => $map->has($style->id)
                && $map[$style->id]->first()['operation_id'] === $op->id);
    }

    public function test_style_operation_management_requires_master_permission(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');

        $this->actingAs($qc)
            ->get(route('admin.sewing.settings.style-operations.index'))
            ->assertForbidden();
    }

    public function test_defect_photo_can_be_attached(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->newSheet($qc);
        $op = $sheet->operations->first();
        $master = SewingDefectMaster::active()->firstOrFail();

        $this->service()->saveResult($qc, $sheet, $op, 1, 2);

        Storage::fake('public');

        $this->actingAs($qc)->post(route('admin.sewing.grid.defect', [$sheet, $op]), [
            'round_no' => 1,
            'defect_master_id' => $master->id,
            'qty' => 1,
            'photo' => UploadedFile::fake()->image('defect.jpg'),
        ])->assertSessionHasNoErrors();

        $log = $sheet->defectLogs()->firstOrFail();
        $this->assertNotNull($log->photo_path);
        Storage::disk('public')->assertExists($log->photo_path);
    }

    public function test_lines_can_be_assigned_to_users(): void
    {
        $this->seedSewing();
        $admin = $this->userWithRole('admin');
        $supervisor = $this->userWithRole('sewing_supervisor');
        $line = $this->makeLine('L1');

        $this->actingAs($admin)
            ->get(route('admin.sewing.settings.line-assignments.index'))
            ->assertOk();

        $this->actingAs($admin)->post(route('admin.sewing.settings.line-assignments.store'), [
            'user_id' => $supervisor->id,
            'sewing_line_ids' => [$line->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sewing_line_user', [
            'user_id' => $supervisor->id,
            'sewing_line_id' => $line->id,
        ]);

        $this->actingAs($admin)->post(route('admin.sewing.settings.line-assignments.store'), [
            'user_id' => $supervisor->id,
            'sewing_line_ids' => [],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sewing_line_user', [
            'user_id' => $supervisor->id,
            'sewing_line_id' => $line->id,
        ]);
    }

    public function test_line_assignment_requires_master_permission(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');

        $this->actingAs($qc)
            ->get(route('admin.sewing.settings.line-assignments.index'))
            ->assertForbidden();
    }

    public function test_dashboard_breaks_down_top_defects_by_line_style_operation_and_operator(): void
    {
        $this->seedSewing();
        $qc = $this->userWithRole('sewing_qc');
        $line = $this->makeLine('L9');
        $operator = SewingOperator::create([
            'employee_code' => 'E1',
            'name' => 'Op One',
            'sewing_line_id' => $line->id,
            'is_active' => true,
        ]);

        $sheet = $this->service()->createSheet($qc, [
            'inspection_date' => now()->toDateString(),
            'sewing_line_id' => $line->id,
            'style_number' => 'STY-9',
        ], [
            ['operation_name' => 'Attach collar', 'sewing_operator_id' => $operator->id],
        ], 4);

        $op = $sheet->operations->first();
        $master = SewingDefectMaster::active()->firstOrFail();

        $this->service()->saveResult($qc, $sheet, $op, 1, 2);
        $this->service()->logDefect($qc, $sheet, $op, 1, $master->id, 1);

        $dash = app(SewingDashboardService::class);

        $byLine = $dash->topDefectsByLine($qc, []);
        $this->assertSame('L9', $byLine->first()->label);
        $this->assertSame(1, (int) $byLine->first()->total);

        $this->assertSame('STY-9', $dash->topDefectsByStyle($qc, [])->first()->label);
        $this->assertSame('Attach collar', $dash->topDefectsByOperation($qc, [])->first()->label);
        $this->assertSame('Op One', $dash->topDefectsByOperator($qc, [])->first()->label);

        $this->actingAs($qc)
            ->get(route('admin.sewing.dashboard'))
            ->assertOk()
            ->assertViewHas('topByLine', fn ($rows) => $rows->first()->label === 'L9')
            ->assertViewHas('topByOperator', fn ($rows) => $rows->first()->label === 'Op One');
    }
}
