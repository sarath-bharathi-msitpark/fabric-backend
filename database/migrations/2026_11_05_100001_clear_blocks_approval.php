<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Checkpoint rules configuration was removed from the checklist admin, so
        // no checkpoint should block stage approval anymore.
        DB::table('cutting_checkpoints')->update(['blocks_approval' => false]);
    }

    public function down(): void
    {
        // Non-reversible data correction.
    }
};
