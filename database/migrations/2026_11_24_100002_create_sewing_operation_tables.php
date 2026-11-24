<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Operation catalogue (reusable operation names)
        Schema::create('sewing_operations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('default_machine_id')->nullable()->constrained('sewing_machines')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name');
        });

        // Operations for a specific style, in sewing sequence
        Schema::create('sewing_style_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('style_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sewing_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sewing_machine_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sequence_no')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['style_id', 'sewing_operation_id']);
            $table->index(['style_id', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_style_operations');
        Schema::dropIfExists('sewing_operations');
    }
};
