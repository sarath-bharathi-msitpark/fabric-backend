<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fabric_record_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('alert_type', ['delay', 'rejection', 'quality', 'shade']);
            $table->enum('severity', ['yellow', 'red']);
            $table->text('message');
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['is_resolved', 'alert_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
