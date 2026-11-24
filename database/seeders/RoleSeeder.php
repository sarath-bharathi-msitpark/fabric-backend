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
            // Sewing Inline Inspection
            'view sewing dashboard', 'view sewing register', 'create sewing inspection', 'edit sewing inspection',
            'signoff sewing qc', 'signoff sewing supervisor', 'signoff sewing pm', 'signoff sewing qam',
            'manage sewing masters', 'manage sewing rules', 'view sewing defect log',
            'view sewing reports', 'export sewing reports',
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

        // Sewing Inline Inspection roles
        $sewingQc = Role::firstOrCreate(['name' => 'sewing_qc', 'guard_name' => 'web']);
        $sewingSupervisor = Role::firstOrCreate(['name' => 'sewing_supervisor', 'guard_name' => 'web']);
        $productionManager = Role::firstOrCreate(['name' => 'production_manager', 'guard_name' => 'web']);

        $admin->syncPermissions(Permission::all());

        $manager->syncPermissions([
            'upload data', 'edit records', 'manage suppliers',
            'manage buyers', 'manage styles', 'view dashboard',
            'export reports', 'resolve alerts',
            'view cutting dashboard', 'view cutting register', 'export cutting reports',
            'create cutting audit', 'edit cutting header', 'submit cutting stage',
            'signoff lay', 'signoff cut_panel', 'signoff bundling', 'release bundles',
            'view sewing dashboard', 'view sewing register', 'create sewing inspection', 'edit sewing inspection',
            'view sewing defect log', 'view sewing reports', 'export sewing reports',
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
            'view sewing dashboard', 'view sewing register', 'signoff sewing qam',
            'view sewing defect log', 'view sewing reports', 'export sewing reports',
            'manage sewing masters', 'manage sewing rules',
        ]);

        $management->syncPermissions([
            'view cutting dashboard', 'view cutting register', 'export cutting reports',
            'view sewing dashboard', 'view sewing register', 'view sewing reports', 'export sewing reports',
        ]);

        $merchandiser->syncPermissions([
            'view cutting dashboard', 'view cutting register',
            'view sewing dashboard', 'view sewing register',
        ]);

        // Sewing Inline Inspection roles
        $sewingQc->syncPermissions([
            'view sewing dashboard', 'view sewing register', 'create sewing inspection', 'edit sewing inspection',
            'signoff sewing qc', 'view sewing defect log',
        ]);

        $sewingSupervisor->syncPermissions([
            'view sewing dashboard', 'view sewing register', 'edit sewing inspection',
            'signoff sewing supervisor', 'view sewing defect log', 'view sewing reports',
        ]);

        $productionManager->syncPermissions([
            'view sewing dashboard', 'view sewing register', 'signoff sewing pm',
            'view sewing defect log', 'view sewing reports', 'export sewing reports',
        ]);
    }
}
