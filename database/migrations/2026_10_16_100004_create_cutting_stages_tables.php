<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutting_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_audit_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 20);
            $table->string('status', 30)->default('draft');
            $table->string('result', 30)->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('inspection_date')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('cutting_checklist_templates')->nullOnDelete();
            $table->unsignedInteger('checklist_version')->nullable();
            $table->timestamps();

            $table->unique(['cutting_audit_id', 'stage']);
        });

        Schema::create('cutting_checkpoint_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_stage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cutting_checkpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->string('checkpoint_name');
            $table->string('result', 10)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('cutting_stage_id');
        });

        Schema::create('cutting_checkpoint_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_checkpoint_result_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });

        Schema::create('cutting_size_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_audit_id')->constrained()->cascadeOnDelete();
            $table->string('size_name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('top_result')->nullable();
            $table->boolean('middle_result')->nullable();
            $table->boolean('bottom_result')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('cutting_audit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_size_patterns');
        Schema::dropIfExists('cutting_checkpoint_photos');
        Schema::dropIfExists('cutting_checkpoint_results');
        Schema::dropIfExists('cutting_stages');
    }
};
