<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function userWithRoles(string ...$roles): User
    {
        $user = User::factory()->create(['role' => $roles[0]]);
        $user->assignRole($roles);

        return $user;
    }

    public function test_user_management_lists_every_assigned_role(): void
    {
        $admin = $this->userWithRoles('admin');
        $this->userWithRoles('cutting_qc', 'cutting_incharge');

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Cutting Qc');
        $response->assertSee('Cutting Incharge');
    }

    public function test_profile_lists_every_assigned_role(): void
    {
        $user = $this->userWithRoles('cutting_qc', 'cutting_incharge');

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('cutting qc');
        $response->assertSee('cutting incharge');
    }

    public function test_all_assigned_role_permissions_apply(): void
    {
        $user = $this->userWithRoles('cutting_qc', 'cutting_incharge');

        $this->assertTrue($user->hasRole('cutting_qc'));
        $this->assertTrue($user->hasRole('cutting_incharge'));
        $this->assertTrue($user->can('submit cutting stage'));
        $this->assertTrue($user->can('signoff lay'));
        $this->assertTrue($user->can('release bundles'));
    }

    public function test_admin_with_a_cutting_role_lands_on_the_main_dashboard(): void
    {
        $user = $this->userWithRoles('admin', 'cutting_qc');

        $this->assertSame('dashboard', $user->homeRoute());
    }
}
