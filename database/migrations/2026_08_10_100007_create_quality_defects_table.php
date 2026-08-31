<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_defects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fabric_record_id')->constrained()->cascadeOnDelete();
            $table->string('defect_type', 100);
            $table->integer('count')->default(0);
            $table->enum('severity', ['minor', 'major', 'critical'])->default('minor');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('defect_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_defects');
    }
};
