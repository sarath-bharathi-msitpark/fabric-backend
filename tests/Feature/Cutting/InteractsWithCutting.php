<?php

namespace Tests\Feature\Cutting;

use App\Models\Buyer;
use App\Models\CuttingAudit;
use App\Models\CuttingTable;
use App\Models\User;
use App\Services\CuttingWorkflowService;
use Database\Seeders\CuttingQmsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

trait InteractsWithCutting
{
    protected function seedCutting(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(CuttingQmsSeeder::class);
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

    protected function makeAudit(User $creator, array $attributes = []): CuttingAudit
    {
        $audit = CuttingAudit::create(array_merge([
            'audit_no' => 'CQA-'.uniqid(),
            'audit_date' => now()->toDateString(),
            'cutting_table_id' => CuttingTable::query()->value('id'),
            'status' => 'in_progress',
            'current_stage' => 'lay',
            'revision' => 1,
            'created_by' => $creator->id,
            'tolerance_pct' => 5,
        ], $attributes));
        $audit->root_id = $audit->id;
        $audit->save();

        return $audit;
    }

    /**
     * Drive a stage through submit + the remaining sign-offs until it is approved.
     */
    protected function approveStage(CuttingAudit $audit, string $stage, User $qc, User $incharge, ?User $qam = null): void
    {
        $workflow = app(CuttingWorkflowService::class);

        $workflow->saveStage($audit, $stage, ['result' => 'approved'], $qc, true);
        $workflow->signOff($audit, $stage, $incharge);

        if ($qam && in_array('qam', $workflow->requiredSignoffRoles($stage), true)) {
            $workflow->signOff($audit, $stage, $qam);
        }
    }
}
