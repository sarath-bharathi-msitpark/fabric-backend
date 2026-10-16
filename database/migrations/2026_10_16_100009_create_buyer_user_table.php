<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('buyer_user')) {
            Schema::create('buyer_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('buyer_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'buyer_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_user');
    }
};
