<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fabric_records', function (Blueprint $table) {
            $table->id();
            $table->date('record_date');
            $table->foreignId('buyer_id')->constrained()->restrictOnDelete();
            $table->foreignId('style_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('lot_no', 50)->unique();
            $table->string('fabric_type', 50);
            $table->string('color', 50);
            $table->decimal('ordered_kg', 12, 2);
            $table->decimal('received_kg', 12, 2)->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('upload_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['buyer_id', 'style_id', 'supplier_id']);
            $table->index('record_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fabric_records');
    }
};
