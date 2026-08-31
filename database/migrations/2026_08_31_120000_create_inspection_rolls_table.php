<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_rolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fabric_record_id')->constrained()->cascadeOnDelete();
            $table->integer('roll_no');
            $table->string('color', 50)->nullable();
            $table->decimal('weight_kgs', 10, 3)->default(0);
            $table->decimal('width_front', 6, 2)->nullable();
            $table->decimal('width_middle', 6, 2)->nullable();
            $table->decimal('width_end', 6, 2)->nullable();
            $table->decimal('gsm', 6, 2)->nullable();
            $table->decimal('roll_length_yards', 10, 1)->nullable();
            $table->decimal('points_per_100_sq_yd', 8, 1)->nullable();
            $table->enum('result', ['pass', 'fail'])->default('pass');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['fabric_record_id', 'roll_no']);
        });

        Schema::table('quality_defects', function (Blueprint $table) {
            $table->foreignId('inspection_roll_id')->nullable()->after('fabric_record_id')->constrained()->nullOnDelete();
            $table->integer('metre_position')->nullable()->after('defect_type');
            $table->integer('points')->nullable()->after('count');
            $table->string('defect_size', 50)->nullable()->after('points');

            $table->index('inspection_roll_id');
        });
    }

    public function down(): void
    {
        Schema::table('quality_defects', function (Blueprint $table) {
            $table->dropIndex(['inspection_roll_id']);
            $table->dropColumn(['inspection_roll_id', 'metre_position', 'points', 'defect_size']);
        });
        Schema::dropIfExists('inspection_rolls');
    }
};
