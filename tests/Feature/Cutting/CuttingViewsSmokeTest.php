<?php

namespace Tests\Feature\Cutting;

use App\Models\CuttingAudit;
use App\Models\CuttingTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuttingViewsSmokeTest extends TestCase
{
    use InteractsWithCutting;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCutting();
    }

    public function test_audit_register_create_show_and_edit_render(): void
    {
        $manager = $this->userWithRole('manager');
        $audit = $this->makeAudit($manager);

        $this->actingAs($manager)->get(route('admin.cutting.audits.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.cutting.audits.create'))->assertOk();
        $this->actingAs($manager)->get(route('admin.cutting.audits.show', $audit))->assertOk();
        $this->actingAs($manager)->get(route('admin.cutting.audits.edit', $audit))->assertOk();
    }

    public function test_every_stage_page_renders(): void
    {
        $manager = $this->userWithRole('manager');
        $audit = $this->makeAudit($manager);

        foreach (CuttingAudit::STAGES as $stage) {
            $this->actingAs($manager)
                ->get(route('admin.cutting.stages.show', [$audit, $stage]))
                ->assertOk();
        }
    }

    public function test_unknown_stage_returns_error(): void
    {
        $manager = $this->userWithRole('manager');
        $audit = $this->makeAudit($manager);

        $this->actingAs($manager)
            ->get(route('admin.cutting.stages.show', [$audit, 'nope']))
            ->assertSessionHasErrors('stage');
    }

    public function test_admin_master_pages_render(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get(route('admin.cutting.checklists.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.cutting.tables.index'))->assertOk();
    }

    public function test_non_admin_cannot_access_master_pages(): void
    {
        $manager = $this->userWithRole('manager');

        $this->actingAs($manager)->get(route('admin.cutting.checklists.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.cutting.tables.index'))->assertForbidden();
    }

    public function test_admin_can_create_and_deactivate_a_cutting_table(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.cutting.tables.store'), [
            'table_no' => 'Table 99',
            'name' => 'Extra Table',
        ])->assertRedirect();

        $table = CuttingTable::where('table_no', 'Table 99')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.cutting.tables.destroy', $table))
            ->assertRedirect();

        $this->assertFalse($table->fresh()->is_active);
    }
}
