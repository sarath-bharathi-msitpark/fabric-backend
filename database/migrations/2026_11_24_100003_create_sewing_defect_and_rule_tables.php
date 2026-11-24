<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DCL with CAPA (UKM-QMS-22A). One row per defect name + root cause.
        Schema::create('sewing_defect_masters', function (Blueprint $table) {
            $table->id();
            $table->string('defect_type', 20);           // sewing / fabric / cutting / trims
            $table->string('capa_code', 10)->unique();    // e.g. S6, F3, C1, T2
            $table->string('defect_name');
            $table->text('root_cause');
            $table->text('corrective_action');
            $table->text('preventive_action');
            $table->string('defect_mock_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('defect_type');
            $table->index(['defect_name', 'is_active']);
        });

        // Configurable inspection rules (pieces per audit, thresholds, rounds)
        Schema::create('sewing_inspection_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedTinyInteger('pieces_per_audit')->default(7);
            $table->unsignedTinyInteger('green_max')->default(0);
            $table->unsignedTinyInteger('amber_max')->default(2);
            $table->unsignedTinyInteger('default_rounds')->default(4);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'version']);
        });

        // Round timing configuration
        Schema::create('sewing_round_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('round_no');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('round_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_round_configs');
        Schema::dropIfExists('sewing_inspection_rules');
        Schema::dropIfExists('sewing_defect_masters');
    }
};
