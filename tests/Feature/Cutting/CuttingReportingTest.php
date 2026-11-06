<?php

namespace Tests\Feature\Cutting;

use App\Http\Controllers\Admin\Cutting\CuttingReportController;
use App\Models\CuttingBundle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CuttingReportingTest extends TestCase
{
    use InteractsWithCutting;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCutting();
    }

    public function test_dashboard_loads_for_cutting_roles(): void
    {
        $qc = $this->userWithRole('cutting_qc');

        $this->actingAs($qc)
            ->get(route('admin.cutting.dashboard'))
            ->assertOk();
    }

    public function test_reports_index_lists_all_report_types(): void
    {
        $manager = $this->userWithRole('manager');

        $this->actingAs($manager)
            ->get(route('admin.cutting.reports.index'))
            ->assertOk()
            ->assertSee('Defect Analysis')
            ->assertSee('Special Approval Log')
            ->assertSee('Sign-off Turnaround')
            ->assertSee('Table-wise Performance');
    }

    public function test_each_report_type_renders(): void
    {
        $manager = $this->userWithRole('manager');

        foreach (array_keys(CuttingReportController::TYPES) as $type) {
            $this->actingAs($manager)
                ->get(route('admin.cutting.reports.show', $type))
                ->assertOk();
        }
    }

    public function test_unknown_report_type_returns_404(): void
    {
        $manager = $this->userWithRole('manager');

        $this->actingAs($manager)
            ->get(route('admin.cutting.reports.show', 'not-a-report'))
            ->assertNotFound();
    }

    public function test_manager_can_export_a_report_to_excel_and_pdf(): void
    {
        $manager = $this->userWithRole('manager');

        $this->actingAs($manager)
            ->get(route('admin.cutting.reports.export', ['type' => 'defect_analysis', 'format' => 'xlsx']))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('admin.cutting.reports.export', ['type' => 'defect_analysis', 'format' => 'pdf']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_audit_skw_qms_17_pdf_downloads(): void
    {
        $manager = $this->userWithRole('manager');
        $audit = $this->makeAudit($manager);

        $response = $this->actingAs($manager)
            ->get(route('admin.cutting.audits.report', $audit));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_audit_report_embeds_checkpoint_photos(): void
    {
        Storage::fake('public');

        $manager = $this->userWithRole('manager');
        $audit = $this->makeAudit($manager);

        $stage = $audit->stages()->create(['stage' => 'lay', 'status' => 'draft']);

        $result = $stage->results()->create([
            'checkpoint_name' => 'Lay ends',
            'result' => 'not_ok',
            'remarks' => 'Uneven',
            'sort_order' => 1,
        ]);

        Storage::disk('public')->put(
            'cutting/checkpoints/test.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='),
        );
        $result->photos()->create(['path' => 'cutting/checkpoints/test.png']);
        $result->photos()->create(['path' => 'cutting/checkpoints/missing.png']);

        $html = view('admin.cutting.reports.audit-pdf', [
            'audit' => $audit->fresh(['stages.results.photos']),
            'company' => 'Test Co',
        ])->render();

        $this->assertStringContainsString('Lay ends', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('Attached photo unavailable.', $html);
    }

    public function test_bundle_card_prints(): void
    {
        $manager = $this->userWithRole('manager');
        $audit = $this->makeAudit($manager);

        $bundle = CuttingBundle::create([
            'cutting_audit_id' => $audit->id,
            'bundle_no' => 'B-001',
            'size' => 'M',
            'panel_numbers' => '1-10',
            'pieces' => 10,
            'status' => 'created',
        ]);

        $this->actingAs($manager)
            ->get(route('admin.cutting.bundles.print', $bundle))
            ->assertOk()
            ->assertSee('B-001');
    }
}
