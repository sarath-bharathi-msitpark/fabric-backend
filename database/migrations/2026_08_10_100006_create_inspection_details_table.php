<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fabric_record_id')->constrained()->cascadeOnDelete();
            $table->decimal('inspected_kg', 12, 2)->default(0);
            $table->decimal('approved_kg', 12, 2)->default(0);
            $table->decimal('rejected_kg', 12, 2)->default(0);
            $table->decimal('gsm_actual', 6, 2)->nullable();
            $table->decimal('gsm_target', 6, 2)->nullable();
            $table->decimal('width_actual', 6, 2)->nullable();
            $table->decimal('width_target', 6, 2)->nullable();
            $table->decimal('pass_pct', 5, 2)->nullable();
            $table->decimal('bowing_pct', 5, 2)->nullable();
            $table->decimal('skewing_pct', 5, 2)->nullable();
            $table->enum('shade_status', ['approved', 'rejected', 'pending'])->default('pending');
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('inspection_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_details');
    }
};
