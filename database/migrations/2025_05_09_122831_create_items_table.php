<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('code', 50);
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
            $table->enum('tracking_mode', ['abstract', 'bulk', 'serialized'])->default('abstract');
            $table->foreignId('unit_id')->constrained('unit_of_measures')->onDelete('restrict');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('serial_number')->nullable(); // for serialized items
            $table->foreignId('status_id')->nullable()->constrained('statuses')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->foreignId('parent_item_id')->nullable()->constrained('items')->onDelete('cascade');
            $table->foreignId('item_relation_id')->nullable()->constrained('items')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->json('specifications')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->unique(['org_id', 'code']);
            $table->index('org_id');
            $table->index('category_id');
            $table->index('user_id');
            $table->index('tracking_mode');
            $table->index('serial_number');
            $table->index('status_id');
            $table->index('parent_item_id');
            $table->index('item_relation_id');
            $table->unique(['org_id', 'serial_number'], 'items_org_serial_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
