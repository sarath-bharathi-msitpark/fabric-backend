<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_name', 100)->unique();
            $table->string('mill_code', 30)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->decimal('on_time_pct', 5, 2)->default(0);
            $table->decimal('quality_pct', 5, 2)->default(0);
            $table->enum('rating', ['excellent', 'good', 'average', 'poor'])->default('average');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
