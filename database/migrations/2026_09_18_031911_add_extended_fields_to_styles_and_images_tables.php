<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('styles', function (Blueprint $table) {
            $table->string('fabric_type', 100)->nullable()->after('status');
            $table->string('color', 100)->nullable()->after('fabric_type');
            $table->decimal('gsm_target', 8, 2)->nullable()->after('color');
            $table->decimal('width_target', 8, 2)->nullable()->after('gsm_target');
        });

        Schema::table('fabric_records', function (Blueprint $table) {
            $table->string('fabric_image_path', 255)->nullable()->after('color');
        });

        Schema::table('quality_defects', function (Blueprint $table) {
            $table->string('defect_image_path', 255)->nullable()->after('defect_size');
        });
    }

    public function down(): void
    {
        Schema::table('styles', function (Blueprint $table) {
            $table->dropColumn(['fabric_type', 'color', 'gsm_target', 'width_target']);
        });

        Schema::table('fabric_records', function (Blueprint $table) {
            $table->dropColumn('fabric_image_path');
        });

        Schema::table('quality_defects', function (Blueprint $table) {
            $table->dropColumn('defect_image_path');
        });
    }
};
