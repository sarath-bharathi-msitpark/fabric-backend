<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutting_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('prefix', 20)->default('CQA-');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedInteger('padding')->default(4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_number_sequences');
    }
};
