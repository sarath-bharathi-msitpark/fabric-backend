<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutting_signoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cutting_stage_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 20);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name');
            $table->string('role', 50);
            $table->string('result', 30)->nullable();
            $table->text('comments')->nullable();
            $table->text('reason')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->unsignedInteger('checklist_version')->nullable();
            $table->timestamp('signed_at');
            $table->timestamps();

            $table->index(['cutting_audit_id', 'stage']);
        });

        Schema::create('cutting_special_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cutting_stage_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 20);
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestamps();

            $table->index(['cutting_audit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_special_approvals');
        Schema::dropIfExists('cutting_signoffs');
    }
};
