<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cutting_checklist_templates')) {
            Schema::create('cutting_checklist_templates', function (Blueprint $table) {
                $table->id();
                $table->string('stage', 20);
                $table->unsignedInteger('version')->default(1);
                $table->json('signoff_roles')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['stage', 'version']);
            });
        }

        if (! Schema::hasTable('cutting_checkpoints')) {
            Schema::create('cutting_checkpoints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cutting_checklist_template_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('mandatory_remarks_on_not_ok')->default(false);
                $table->boolean('blocks_approval')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['cutting_checklist_template_id', 'sort_order'], 'cutting_checkpoints_template_sort_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_checkpoints');
        Schema::dropIfExists('cutting_checklist_templates');
    }
};
