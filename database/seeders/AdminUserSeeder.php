<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@fabricsourcing.in'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('Admin@12345'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        $manager = User::updateOrCreate(
            ['email' => 'manager@fabricsourcing.in'],
            [
                'name' => 'Factory Manager',
                'password' => Hash::make('Manager@12345'),
                'role' => 'manager',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $manager->assignRole('manager');

        $viewer = User::updateOrCreate(
            ['email' => 'viewer@fabricsourcing.in'],
            [
                'name' => 'Read Only Viewer',
                'password' => Hash::make('Viewer@12345'),
                'role' => 'viewer',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $viewer->assignRole('viewer');
    }
}
