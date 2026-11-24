<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_line_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sewing_line_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'sewing_line_id'], 'sewing_line_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_line_user');
    }
};
