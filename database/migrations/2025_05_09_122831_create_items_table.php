<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('items');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('items', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 50);
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
            $table->enum('tracking_mode', ['abstract', 'standard', 'serialized'])->default('abstract');
            $table->foreignUuid('unit_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('serial_number')->nullable();
            $table->foreignUuid('status_id')->nullable()->constrained('statuses')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignUuid('parent_item_id')->nullable()->constrained('items')->cascadeOnDelete();
            $table->foreignUuid('item_relation_id')->nullable()->constrained('items')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->jsonb('specifications')->nullable();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->timestamp('tracking_changed_at')->nullable();
            $table->text('tracking_change_reason')->nullable();
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
            $table->index(['org_id', 'batch_id']);
            $table->unique(['org_id', 'serial_number'], 'items_org_serial_unique');
        });
    }
};
