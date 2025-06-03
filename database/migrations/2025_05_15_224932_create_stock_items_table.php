<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('stock_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->string('serial_number')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->foreignId('status_id')->nullable()->constrained('item_statuses')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('org_id');
            $table->index('stock_id');
            $table->index('item_id');
            $table->index('serial_number');
            $table->index('barcode');
            $table->index('status_id');

            $table->unique(['org_id', 'stock_id', 'item_id', 'serial_number'], 'stock_items_unique_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
