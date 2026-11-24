<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One inspection sheet = one line, one style/PO, one inspection date
        Schema::create('sewing_inspection_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('sheet_no', 50)->unique();
            $table->foreignId('sewing_line_id')->nullable()->constrained()->nullOnDelete();
            $table->date('inspection_date');
            $table->string('shift', 20)->nullable();

            // Order header (auto-filled from style/PO, kept as snapshot)
            $table->foreignId('buyer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('style_id')->nullable()->constrained()->nullOnDelete();
            $table->string('style_number', 100)->nullable();
            $table->string('style_name')->nullable();
            $table->string('color')->nullable();
            $table->string('po_number', 100)->nullable();
            $table->string('lot_number', 100)->nullable();
            $table->string('bundle_no', 100)->nullable();

            $table->foreignId('qc_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft');    // draft / in_progress / signed / locked
            $table->unsignedTinyInteger('current_round')->default(1);
            $table->unsignedTinyInteger('pieces_per_audit')->default(7);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('inspection_date');
            $table->index('status');
            $table->index(['buyer_id', 'style_number']);
            $table->index('sewing_line_id');
        });

        // Operation rows on the sheet (grid rows)
        Schema::create('sewing_inspection_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sewing_inspection_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sewing_operation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sewing_machine_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sewing_operator_id')->nullable()->constrained()->nullOnDelete();
            $table->string('operation_name');
            $table->string('machine_no', 50)->nullable();
            $table->unsignedInteger('sequence_no')->default(1);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['sewing_inspection_sheet_id', 'sequence_no'], 'sewing_insp_ops_sheet_seq_index');
        });

        // Round metadata per sheet (bundle may change per round)
        Schema::create('sewing_inspection_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sewing_inspection_sheet_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('round_no');
            $table->string('bundle_no', 100)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('status', 20)->default('open');    // open / closed
            $table->timestamps();

            $table->unique(['sewing_inspection_sheet_id', 'round_no'], 'sewing_insp_rounds_sheet_round_unique');
        });

        // Grid cell result: one per operation per round (defect count + re-audit)
        Schema::create('sewing_inspection_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sewing_inspection_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sewing_inspection_operation_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('round_no');
            $table->unsignedTinyInteger('defect_count')->default(0);
            $table->string('result', 10)->nullable();         // green / amber / red
            $table->text('corrective_action')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('inspected_at')->nullable();

            // Re-audit (only opened for Red rounds)
            $table->unsignedTinyInteger('re_audit_count')->nullable();
            $table->string('re_audit_result', 20)->nullable(); // cleared / escalated
            $table->text('re_audit_note')->nullable();
            $table->timestamp('re_audited_at')->nullable();

            $table->timestamps();

            $table->unique(['sewing_inspection_operation_id', 'round_no'], 'sewing_results_op_round_unique');
            $table->index('result');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_inspection_results');
        Schema::dropIfExists('sewing_inspection_rounds');
        Schema::dropIfExists('sewing_inspection_operations');
        Schema::dropIfExists('sewing_inspection_sheets');
    }
};
