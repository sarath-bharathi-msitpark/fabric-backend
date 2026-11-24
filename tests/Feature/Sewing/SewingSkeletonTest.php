<?php

namespace Tests\Feature\Sewing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SewingSkeletonTest extends TestCase
{
    use InteractsWithSewing;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSewing();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.sewing.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.sewing.sheets.index'))->assertRedirect(route('login'));
    }

    public function test_dashboard_renders_for_a_sewing_role(): void
    {
        $qc = $this->userWithRole('sewing_qc');

        $this->actingAs($qc)
            ->get(route('admin.sewing.dashboard'))
            ->assertOk()
            ->assertSee('Sewing Inline Inspection');
    }

    public function test_viewer_is_forbidden_from_the_module(): void
    {
        $viewer = $this->userWithRole('viewer');

        $this->actingAs($viewer)->get(route('admin.sewing.dashboard'))->assertForbidden();
        $this->actingAs($viewer)->get(route('admin.sewing.sheets.index'))->assertForbidden();
    }

    public function test_placeholder_pages_render(): void
    {
        $qc = $this->userWithRole('sewing_qc');
        $pm = $this->userWithRole('production_manager');

        foreach ([
            route('admin.sewing.sheets.index'),
            route('admin.sewing.sheets.create'),
            route('admin.sewing.live-board.index'),
            route('admin.sewing.defect-log.index'),
        ] as $url) {
            $this->actingAs($qc)->get($url)->assertOk();
        }

        foreach ([
            route('admin.sewing.repeat-red.index'),
            route('admin.sewing.reports.index'),
            route('admin.sewing.reports.show', 'defect-analysis'),
            route('admin.sewing.reports.show', 're-audit'),
        ] as $url) {
            $this->actingAs($pm)->get($url)->assertOk();
        }
    }

    public function test_sheet_show_and_grid_render(): void
    {
        $qc = $this->userWithRole('sewing_qc');
        $sheet = $this->makeSheet($qc);

        $this->actingAs($qc)->get(route('admin.sewing.sheets.show', $sheet))->assertOk();
        $this->actingAs($qc)->get(route('admin.sewing.grid.show', $sheet))->assertOk();
    }

    public function test_live_board_data_returns_json(): void
    {
        $qc = $this->userWithRole('sewing_qc');

        $this->actingAs($qc)
            ->get(route('admin.sewing.live-board.data'))
            ->assertOk()
            ->assertJsonStructure(['lines']);
    }

    public function test_settings_pages_render_for_admin(): void
    {
        $admin = $this->userWithRole('admin');

        foreach ([
            route('admin.sewing.settings.lines.index'),
            route('admin.sewing.settings.machines.index'),
            route('admin.sewing.settings.operators.index'),
            route('admin.sewing.settings.operations.index'),
            route('admin.sewing.settings.defects.index'),
            route('admin.sewing.settings.rules.index'),
            route('admin.sewing.settings.rounds.index'),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_settings_are_forbidden_for_non_admin_roles(): void
    {
        $manager = $this->userWithRole('manager');
        $qc = $this->userWithRole('sewing_qc');

        $this->actingAs($manager)->get(route('admin.sewing.settings.lines.index'))->assertForbidden();
        $this->actingAs($qc)->get(route('admin.sewing.settings.rules.index'))->assertForbidden();
    }
}
