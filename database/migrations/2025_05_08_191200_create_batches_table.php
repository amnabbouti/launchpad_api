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
        Schema::dropIfExists('batches');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('batches', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('batch_number')->nullable();
            $table->date('received_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignUuid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['org_id', 'batch_number']);
            $table->index('org_id');
            $table->index(['org_id', 'id']);
            $table->index(['org_id', 'batch_number']);
            $table->index(['org_id', 'received_date']);
            $table->index(['org_id', 'expiry_date']);
            $table->index(['org_id', 'supplier_id']);
        });
    }
};
