<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'upload data', 'edit records', 'delete records',
            'manage users', 'manage suppliers', 'manage buyers', 'manage styles',
            'view dashboard', 'export reports', 'resolve alerts',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        $admin->syncPermissions(Permission::all());
        $manager->syncPermissions([
            'upload data', 'edit records', 'manage suppliers',
            'manage buyers', 'manage styles', 'view dashboard',
            'export reports', 'resolve alerts',
        ]);
        $viewer->syncPermissions(['view dashboard', 'export reports']);
    }
}
