<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutting_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_audit_id')->constrained()->cascadeOnDelete();
            $table->string('bundle_no', 100);
            $table->string('size', 50)->nullable();
            $table->string('panel_numbers')->nullable();
            $table->unsignedInteger('pieces')->default(0);
            $table->string('status', 20)->default('created');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['cutting_audit_id', 'bundle_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_bundles');
    }
};
