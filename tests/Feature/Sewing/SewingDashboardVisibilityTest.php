<?php

namespace Tests\Feature\Sewing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SewingDashboardVisibilityTest extends TestCase
{
    use InteractsWithSewing;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSewing();
    }

    public function test_merchandiser_only_sees_sheets_for_assigned_buyers(): void
    {
        $admin = $this->userWithRole('admin');
        $merchandiser = $this->userWithRole('merchandiser');

        $mine = $this->makeBuyer('Mine');
        $other = $this->makeBuyer('Other');
        $merchandiser->assignedBuyers()->attach($mine->id);

        $this->makeSheet($admin, ['buyer_id' => $mine->id]);
        $this->makeSheet($admin, ['buyer_id' => $other->id]);

        $response = $this->actingAs($merchandiser)->get(route('admin.sewing.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', fn ($stats) => $stats['total'] === 1);
    }

    public function test_admin_sees_all_sheets_on_dashboard(): void
    {
        $admin = $this->userWithRole('admin');

        $this->makeSheet($admin, ['buyer_id' => $this->makeBuyer('A')->id]);
        $this->makeSheet($admin, ['buyer_id' => $this->makeBuyer('B')->id]);

        $this->actingAs($admin)
            ->get(route('admin.sewing.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === 2);
    }

    public function test_home_route_for_sewing_roles(): void
    {
        $this->assertSame('admin.sewing.dashboard', $this->userWithRole('sewing_qc')->homeRoute());
        $this->assertSame('admin.sewing.dashboard', $this->userWithRole('sewing_supervisor')->homeRoute());
        $this->assertSame('admin.sewing.dashboard', $this->userWithRole('production_manager')->homeRoute());
    }

    public function test_home_route_for_existing_roles_is_unchanged(): void
    {
        $this->assertSame('dashboard', $this->userWithRole('admin')->homeRoute());
        $this->assertSame('dashboard', $this->userWithRole('viewer')->homeRoute());
        $this->assertSame('admin.cutting.dashboard', $this->userWithRole('qam')->homeRoute());
        $this->assertSame('admin.cutting.dashboard', $this->userWithRole('merchandiser')->homeRoute());
    }
}
