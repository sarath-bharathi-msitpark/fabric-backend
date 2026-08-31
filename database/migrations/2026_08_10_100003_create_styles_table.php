<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('styles', function (Blueprint $table) {
            $table->id();
            $table->string('style_number', 50)->unique();
            $table->foreignId('buyer_id')->constrained()->cascadeOnDelete();
            $table->decimal('order_quantity', 12, 2);
            $table->date('target_date');
            $table->enum('status', ['planning', 'in_progress', 'completed', 'on_hold'])->default('planning');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('styles');
    }
};
