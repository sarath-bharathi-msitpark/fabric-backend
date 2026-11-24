<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_lines', function (Blueprint $table) {
            $table->id();
            $table->string('line_no', 50)->unique();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sewing_machines', function (Blueprint $table) {
            $table->id();
            $table->string('machine_no', 50)->unique();
            $table->string('type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sewing_operators', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 50)->unique();
            $table->string('name');
            $table->foreignId('sewing_line_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('sewing_line_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_operators');
        Schema::dropIfExists('sewing_machines');
        Schema::dropIfExists('sewing_lines');
    }
};
