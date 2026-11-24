<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Defect log entry. CAPA fields are snapshotted so past entries stay immutable (BR-12).
        Schema::create('sewing_defect_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sewing_inspection_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sewing_inspection_operation_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('round_no');
            $table->boolean('is_reaudit')->default(false);

            $table->foreignId('sewing_defect_master_id')->nullable()->constrained()->nullOnDelete();
            $table->string('defect_type', 20);
            $table->string('capa_code', 10);
            $table->string('defect_name');
            $table->text('root_cause');
            $table->text('corrective_action');
            $table->text('preventive_action');

            $table->unsignedTinyInteger('qty')->default(1);
            $table->string('photo_path')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('logged_at')->nullable();
            $table->timestamps();

            $table->index(['sewing_inspection_sheet_id', 'round_no']);
            $table->index('capa_code');
            $table->index('defect_name');
        });

        // Four sign-offs: qc -> supervisor -> pm -> qam
        Schema::create('sewing_signoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sewing_inspection_sheet_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name');
            $table->text('comments')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('signed_at');
            $table->timestamps();

            $table->unique(['sewing_inspection_sheet_id', 'role'], 'sewing_signoffs_sheet_role_unique');
        });

        // Audit trail for every change (user, time, old/new value, reason)
        Schema::create('sewing_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('action', 30);
            $table->string('field')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_audit_logs');
        Schema::dropIfExists('sewing_signoffs');
        Schema::dropIfExists('sewing_defect_logs');
    }
};
