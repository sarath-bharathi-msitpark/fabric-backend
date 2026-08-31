<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_batches', function (Blueprint $table) {
            $table->id();
            $table->string('file_name', 255);
            $table->enum('upload_type', ['new_records', 'daily_update']);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->integer('total_rows')->default(0);
            $table->integer('success_rows')->default(0);
            $table->integer('error_rows')->default(0);
            $table->enum('status', ['validating', 'completed', 'failed'])->default('validating');
            $table->json('error_log')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_batches');
    }
};
