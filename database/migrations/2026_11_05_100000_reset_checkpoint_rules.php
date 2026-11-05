<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remarks are required on Not OK for every checkpoint (global rule), so the
        // per-checkpoint flag is no longer used.
        DB::table('cutting_checkpoints')->update(['mandatory_remarks_on_not_ok' => false]);

        // Stripe/check matching must not block stage approval.
        DB::table('cutting_checkpoints')
            ->where('name', 'Stripe/check matching')
            ->update(['blocks_approval' => false]);
    }

    public function down(): void
    {
        // Non-reversible data correction.
    }
};
