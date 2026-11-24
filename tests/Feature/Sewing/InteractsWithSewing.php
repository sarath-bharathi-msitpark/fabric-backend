<?php

namespace Tests\Feature\Sewing;

use App\Models\Buyer;
use App\Models\SewingInspectionSheet;
use App\Models\SewingLine;
use App\Models\User;
use App\Services\SewingNumberService;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SewingQmsSeeder;
use Illuminate\Support\Facades\Hash;

trait InteractsWithSewing
{
    protected function seedSewing(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(SewingQmsSeeder::class);
    }

    protected function userWithRole(string ...$roles): User
    {
        $user = User::create([
            'name' => 'User '.uniqid(),
            'email' => uniqid().'@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => $roles[0] ?? 'viewer',
            'email_verified_at' => now(),
        ]);
        $user->assignRole($roles);

        return $user;
    }

    protected function makeBuyer(?string $name = null): Buyer
    {
        return Buyer::create([
            'buyer_name' => $name ?? 'Buyer '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function makeLine(?string $lineNo = null): SewingLine
    {
        return SewingLine::create([
            'line_no' => $lineNo ?? 'L'.uniqid(),
            'name' => 'Line '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function makeSheet(User $creator, array $attributes = []): SewingInspectionSheet
    {
        return SewingInspectionSheet::create(array_merge([
            'sheet_no' => app(SewingNumberService::class)->nextSheetNumber(),
            'inspection_date' => now()->toDateString(),
            'status' => SewingInspectionSheet::STATUS_DRAFT,
            'current_round' => 1,
            'pieces_per_audit' => 7,
            'created_by' => $creator->id,
        ], $attributes));
    }
}
