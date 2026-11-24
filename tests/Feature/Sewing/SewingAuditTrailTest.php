<?php

namespace Tests\Feature\Sewing;

use App\Models\SewingAuditLog;
use App\Models\SewingLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SewingAuditTrailTest extends TestCase
{
    use InteractsWithSewing;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSewing();
    }

    public function test_creating_a_master_writes_a_created_snapshot(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin);

        $line = SewingLine::create([
            'line_no' => 'L-AUD',
            'name' => 'Audited Line',
            'is_active' => true,
        ]);

        $log = SewingAuditLog::where('auditable_type', SewingLine::class)
            ->where('auditable_id', $line->id)
            ->where('action', 'created')
            ->firstOrFail();

        $this->assertSame($admin->id, $log->user_id);
        $this->assertStringContainsString('L-AUD', (string) $log->new_value);
    }

    public function test_updating_a_master_writes_one_row_per_changed_field(): void
    {
        $admin = $this->userWithRole('admin');
        $line = $this->makeLine('L-OLD');

        SewingAuditLog::query()->delete();
        $this->actingAs($admin);

        $line->update(['name' => 'New Name']);

        $logs = SewingAuditLog::where('auditable_id', $line->id)->get();

        $this->assertCount(1, $logs);
        $this->assertSame('updated', $logs->first()->action);
        $this->assertSame('name', $logs->first()->field);
        $this->assertSame('New Name', $logs->first()->new_value);
    }

    public function test_deactivating_a_master_logs_the_active_flag_change(): void
    {
        $admin = $this->userWithRole('admin');
        $line = $this->makeLine('L-DEACT');

        SewingAuditLog::query()->delete();
        $this->actingAs($admin);

        $line->update(['is_active' => false]);

        $log = SewingAuditLog::where('auditable_id', $line->id)
            ->where('field', 'is_active')
            ->firstOrFail();

        $this->assertSame('1', $log->old_value);
        $this->assertSame('0', $log->new_value);
    }

    public function test_a_no_op_update_writes_nothing(): void
    {
        $admin = $this->userWithRole('admin');
        $line = $this->makeLine('L-NOOP');

        SewingAuditLog::query()->delete();
        $this->actingAs($admin);

        $line->update(['line_no' => 'L-NOOP']);

        $this->assertSame(0, SewingAuditLog::where('auditable_id', $line->id)->count());
    }

    public function test_explicit_correction_records_reason_and_user(): void
    {
        $admin = $this->userWithRole('admin');
        $line = $this->makeLine('L-FIX');

        SewingAuditLog::query()->delete();
        $this->actingAs($admin);

        $line->logAudit('corrected', 'name', 'Wrong', 'Right', 'Typo correction');

        $log = SewingAuditLog::where('auditable_id', $line->id)
            ->where('action', 'corrected')
            ->firstOrFail();

        $this->assertSame('Typo correction', $log->reason);
        $this->assertSame('Wrong', $log->old_value);
        $this->assertSame('Right', $log->new_value);
        $this->assertSame($admin->id, $log->user_id);
    }

    public function test_audit_log_resolves_the_morph_relation(): void
    {
        $line = $this->makeLine('L-MORPH');

        $log = SewingAuditLog::where('auditable_type', SewingLine::class)
            ->where('auditable_id', $line->id)
            ->firstOrFail();

        $this->assertInstanceOf(SewingLine::class, $log->auditable);
        $this->assertSame('L-MORPH', $log->auditable->line_no);
    }
}
