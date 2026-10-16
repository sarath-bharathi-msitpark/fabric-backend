<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutting_audits', function (Blueprint $table) {
            $table->id();
            $table->string('audit_no', 50)->unique();
            $table->foreignId('cutting_table_id')->nullable()->constrained()->nullOnDelete();
            $table->date('audit_date');
            $table->string('shift', 20)->nullable();

            // Order header
            $table->foreignId('buyer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('style_number', 100)->nullable();
            $table->string('style_name')->nullable();
            $table->string('po_number', 100)->nullable();
            $table->string('lot_number', 100)->nullable();
            $table->string('fabric')->nullable();
            $table->string('colour')->nullable();
            $table->string('dia', 50)->nullable();

            // Lay details
            $table->string('lay_number', 100)->nullable();
            $table->string('cut_number', 100)->nullable();
            $table->unsignedInteger('number_of_sizes')->default(0);
            $table->string('lay_method', 20)->default('manual');
            $table->string('marking_type')->nullable();
            $table->decimal('required_lay_length', 10, 2)->nullable();
            $table->decimal('required_lay_width', 10, 2)->nullable();
            $table->decimal('actual_lay_length', 10, 2)->nullable();
            $table->decimal('actual_lay_width', 10, 2)->nullable();
            $table->unsignedInteger('required_plies')->nullable();
            $table->unsignedInteger('actual_plies')->nullable();
            $table->decimal('actual_lay_height', 10, 2)->nullable();
            $table->unsignedInteger('total_parts')->nullable();
            $table->decimal('tolerance_pct', 5, 2)->default(5.00);

            // Workflow
            $table->string('status', 20)->default('draft');
            $table->string('current_stage', 20)->default('lay');
            $table->unsignedInteger('revision')->default(1);
            $table->foreignId('parent_id')->nullable()->constrained('cutting_audits')->nullOnDelete();
            $table->unsignedBigInteger('root_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('audit_date');
            $table->index('status');
            $table->index('current_stage');
            $table->index(['buyer_id', 'style_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_audits');
    }
};
