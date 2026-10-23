<?php

namespace Tests\Feature\Cutting;

use App\Models\CuttingAudit;
use App\Models\CuttingChecklistTemplate;
use App\Models\CuttingTable;
use App\Models\User;
use App\Services\CuttingNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuttingAuditManagementTest extends TestCase
{
    use InteractsWithCutting;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCutting();
    }

    public function test_audit_numbering_is_sequential(): void
    {
        $service = app(CuttingNumberService::class);

        $this->assertSame('CQA-1001', $service->nextAuditNumber());
        $this->assertSame('CQA-1002', $service->nextAuditNumber());
        $this->assertSame('CQA-1003', $service->nextAuditNumber());
    }

    public function test_qc_can_create_an_audit_and_is_redirected_to_lay_stage(): void
    {
        $qc = $this->userWithRole('cutting_qc');
        $buyer = $this->makeBuyer();
        $table = CuttingTable::query()->first();

        $response = $this->actingAs($qc)->post(route('admin.cutting.audits.store'), [
            'audit_date' => now()->toDateString(),
            'buyer_id' => $buyer->id,
            'cutting_table_id' => $table->id,
            'style_number' => 'ST-100',
            'number_of_sizes' => 3,
            'lay_method' => 'manual',
        ]);

        $audit = CuttingAudit::firstOrFail();

        $this->assertSame('CQA-1001', $audit->audit_no);
        $this->assertSame('in_progress', $audit->status);
        $this->assertSame('lay', $audit->current_stage);
        $this->assertSame($audit->id, $audit->root_id);
        $this->assertSame($qc->id, $audit->created_by);

        $response->assertRedirect(route('admin.cutting.stages.show', [$audit, 'lay']));
    }

    public function test_merchandiser_only_sees_audits_for_assigned_buyers(): void
    {
        $admin = $this->userWithRole('admin');
        $merchandiser = $this->userWithRole('merchandiser');

        $mine = $this->makeBuyer('Mine');
        $other = $this->makeBuyer('Other');
        $merchandiser->assignedBuyers()->attach($mine->id);

        $visible = $this->makeAudit($admin, ['buyer_id' => $mine->id]);
        $hidden = $this->makeAudit($admin, ['buyer_id' => $other->id]);

        $response = $this->actingAs($merchandiser)->get(route('admin.cutting.audits.index'));

        $response->assertOk();
        $response->assertSee($visible->audit_no);
        $response->assertDontSee($hidden->audit_no);

        $this->actingAs($merchandiser)
            ->get(route('admin.cutting.audits.show', $hidden))
            ->assertForbidden();
    }

    public function test_viewer_cannot_create_an_audit(): void
    {
        $viewer = $this->userWithRole('viewer');

        $this->actingAs($viewer)
            ->get(route('admin.cutting.audits.create'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.cutting.audits.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_assign_cutting_roles_to_a_user(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New QC',
            'email' => 'newqc@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['cutting_qc', 'cutting_incharge'],
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'newqc@example.com')->firstOrFail();
        $this->assertTrue($user->hasAllRoles(['cutting_qc', 'cutting_incharge']));
        $this->assertSame('cutting_qc', $user->role);
    }

    public function test_admin_can_deactivate_a_checkpoint(): void
    {
        $admin = $this->userWithRole('admin');
        $checkpoint = CuttingChecklistTemplate::currentFor('lay')->activeCheckpoints()->firstOrFail();

        $this->actingAs($admin)->put(route('admin.cutting.checkpoints.update', $checkpoint), [
            'name' => $checkpoint->name,
        ])->assertRedirect();

        $this->assertFalse($checkpoint->fresh()->is_active);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = $this->userWithRole('admin');
        $target = $this->userWithRole('viewer');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
