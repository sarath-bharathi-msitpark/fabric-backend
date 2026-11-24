<?php

namespace Tests\Feature\Sewing;

use App\Models\SewingDefectMaster;
use App\Models\SewingInspectionRule;
use App\Models\SewingLine;
use App\Models\SewingMachine;
use App\Models\SewingOperation;
use App\Models\SewingOperator;
use App\Models\SewingRoundConfig;
use App\Services\SewingNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SewingMasterManagementTest extends TestCase
{
    use InteractsWithSewing;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSewing();
    }

    public function test_numbering_is_sequential(): void
    {
        $service = app(SewingNumberService::class);

        $this->assertSame('SII-0001', $service->nextSheetNumber());
        $this->assertSame('SII-0002', $service->nextSheetNumber());
        $this->assertSame('SII-0003', $service->nextSheetNumber());
    }

    public function test_admin_can_create_update_and_deactivate_a_line(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.sewing.settings.lines.store'), [
            'line_no' => 'L99',
            'name' => 'Extra Line',
        ])->assertRedirect();

        $line = SewingLine::where('line_no', 'L99')->firstOrFail();
        $this->assertTrue($line->is_active);

        $this->actingAs($admin)->put(route('admin.sewing.settings.lines.update', $line), [
            'line_no' => 'L99',
            'name' => 'Renamed Line',
        ])->assertRedirect();
        $this->assertSame('Renamed Line', $line->fresh()->name);

        $this->actingAs($admin)->delete(route('admin.sewing.settings.lines.destroy', $line))->assertRedirect();
        $this->assertFalse($line->fresh()->is_active);
    }

    public function test_admin_can_create_a_machine_operator_operation_and_defect(): void
    {
        $admin = $this->userWithRole('admin');
        $line = $this->makeLine();

        $this->actingAs($admin)->post(route('admin.sewing.settings.machines.store'), [
            'machine_no' => 'M-01',
            'type' => 'Single needle',
        ])->assertRedirect();
        $machine = SewingMachine::where('machine_no', 'M-01')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.sewing.settings.operators.store'), [
            'employee_code' => 'EMP-01',
            'name' => 'Operator One',
            'sewing_line_id' => $line->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('sewing_operators', ['employee_code' => 'EMP-01', 'sewing_line_id' => $line->id]);

        $this->actingAs($admin)->post(route('admin.sewing.settings.operations.store'), [
            'name' => 'Shoulder join',
            'default_machine_id' => $machine->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('sewing_operations', ['name' => 'Shoulder join', 'default_machine_id' => $machine->id]);

        $this->actingAs($admin)->post(route('admin.sewing.settings.defects.store'), [
            'defect_type' => SewingDefectMaster::TYPE_SEWING,
            'capa_code' => 'S99',
            'defect_name' => 'Test defect',
            'root_cause' => 'Test cause',
            'corrective_action' => 'Test corrective',
            'preventive_action' => 'Test preventive',
        ])->assertRedirect();
        $this->assertDatabaseHas('sewing_defect_masters', ['capa_code' => 'S99', 'is_active' => true]);
    }

    public function test_qam_can_manage_masters(): void
    {
        $qam = $this->userWithRole('qam');

        $this->actingAs($qam)->post(route('admin.sewing.settings.lines.store'), [
            'line_no' => 'L-QAM',
        ])->assertRedirect();

        $this->assertDatabaseHas('sewing_lines', ['line_no' => 'L-QAM']);
    }

    public function test_manager_cannot_manage_masters(): void
    {
        $manager = $this->userWithRole('manager');

        $this->actingAs($manager)->post(route('admin.sewing.settings.lines.store'), [
            'line_no' => 'L-NOPE',
        ])->assertForbidden();

        $this->assertDatabaseMissing('sewing_lines', ['line_no' => 'L-NOPE']);
    }

    public function test_defect_capa_code_is_unique(): void
    {
        $admin = $this->userWithRole('admin');

        $payload = [
            'defect_type' => SewingDefectMaster::TYPE_SEWING,
            'capa_code' => 'S1',
            'defect_name' => 'Duplicate',
            'root_cause' => 'x',
            'corrective_action' => 'y',
            'preventive_action' => 'z',
        ];

        $this->actingAs($admin)
            ->post(route('admin.sewing.settings.defects.store'), $payload)
            ->assertSessionHasErrors('capa_code');
    }

    public function test_rules_can_be_updated_and_validate_amber_above_green(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->put(route('admin.sewing.settings.rules.update'), [
            'pieces_per_audit' => 7,
            'green_max' => 0,
            'amber_max' => 3,
            'default_rounds' => 5,
        ])->assertRedirect();

        $rule = SewingInspectionRule::current();
        $this->assertSame(3, $rule->amber_max);
        $this->assertSame(5, $rule->default_rounds);

        $this->actingAs($admin)->put(route('admin.sewing.settings.rules.update'), [
            'pieces_per_audit' => 7,
            'green_max' => 3,
            'amber_max' => 2,
            'default_rounds' => 4,
        ])->assertSessionHasErrors('amber_max');
    }

    public function test_rounds_can_be_updated(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->put(route('admin.sewing.settings.rounds.update'), [
            'rounds' => [
                ['round_no' => 1, 'start_time' => '08:00', 'end_time' => '09:00', 'is_active' => 1],
                ['round_no' => 2, 'start_time' => '09:00', 'end_time' => '10:00', 'is_active' => 1],
            ],
        ])->assertRedirect();

        $first = SewingRoundConfig::where('round_no', 1)->firstOrFail();
        $second = SewingRoundConfig::where('round_no', 2)->firstOrFail();

        $this->assertTrue($first->is_active);
        $this->assertStringStartsWith('08:00', (string) $first->start_time);
        $this->assertTrue($second->is_active);
        $this->assertStringStartsWith('09:00', (string) $second->start_time);
    }

    public function test_rule_result_for_count_boundaries(): void
    {
        $rule = SewingInspectionRule::current();

        $this->assertSame('green', $rule->resultForCount(0));
        $this->assertSame('amber', $rule->resultForCount(1));
        $this->assertSame('amber', $rule->resultForCount(2));
        $this->assertSame('red', $rule->resultForCount(3));
        $this->assertSame('red', $rule->resultForCount(7));
    }

    public function test_seeded_masters_are_present(): void
    {
        $this->assertSame(32, SewingDefectMaster::count());
        $this->assertSame(4, SewingRoundConfig::count());
        $this->assertSame(7, SewingInspectionRule::current()->pieces_per_audit);
        $this->assertSame(0, SewingOperator::count());
        $this->assertSame(0, SewingOperation::count());
    }
}
