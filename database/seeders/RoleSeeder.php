<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Fabric / existing
            'upload data', 'edit records', 'delete records',
            'manage users', 'manage suppliers', 'manage buyers', 'manage styles',
            'view dashboard', 'export reports', 'resolve alerts',
            // Cutting QMS
            'manage cutting checklists', 'manage cutting tables', 'manage cutting numbering',
            'create cutting audit', 'edit cutting header', 'submit cutting stage',
            'signoff lay', 'signoff cut_panel', 'signoff bundling',
            'approve special approval', 'release bundles',
            'view cutting dashboard', 'view cutting register', 'export cutting reports',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        // Cutting QMS roles
        $cuttingQc = Role::firstOrCreate(['name' => 'cutting_qc', 'guard_name' => 'web']);
        $cuttingIncharge = Role::firstOrCreate(['name' => 'cutting_incharge', 'guard_name' => 'web']);
        $qam = Role::firstOrCreate(['name' => 'qam', 'guard_name' => 'web']);
        $management = Role::firstOrCreate(['name' => 'management', 'guard_name' => 'web']);
        $merchandiser = Role::firstOrCreate(['name' => 'merchandiser', 'guard_name' => 'web']);

        $admin->syncPermissions(Permission::all());

        $manager->syncPermissions([
            'upload data', 'edit records', 'manage suppliers',
            'manage buyers', 'manage styles', 'view dashboard',
            'export reports', 'resolve alerts',
            'view cutting dashboard', 'view cutting register', 'export cutting reports',
            'create cutting audit', 'edit cutting header', 'submit cutting stage',
            'signoff lay', 'signoff cut_panel', 'signoff bundling', 'release bundles',
        ]);

        $viewer->syncPermissions(['view dashboard', 'export reports', 'view cutting dashboard', 'view cutting register']);

        $cuttingQc->syncPermissions([
            'view cutting dashboard', 'view cutting register',
            'create cutting audit', 'edit cutting header', 'submit cutting stage',
        ]);

        $cuttingIncharge->syncPermissions([
            'view cutting dashboard', 'view cutting register', 'export cutting reports',
            'create cutting audit', 'edit cutting header', 'submit cutting stage',
            'signoff lay', 'signoff cut_panel', 'signoff bundling', 'release bundles',
        ]);

        $qam->syncPermissions([
            'view cutting dashboard', 'view cutting register', 'export cutting reports',
            'signoff lay', 'signoff cut_panel', 'signoff bundling',
            'approve special approval', 'release bundles',
        ]);

        $management->syncPermissions([
            'view cutting dashboard', 'view cutting register', 'export cutting reports',
        ]);

        $merchandiser->syncPermissions([
            'view cutting dashboard', 'view cutting register',
        ]);
    }
}
