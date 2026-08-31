<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->id();
            $table->string('kpi_key', 50)->unique();
            $table->decimal('target_value', 6, 2);
            $table->enum('comparison', ['gte', 'lte', 'eq'])->default('gte');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_targets');
    }
};
