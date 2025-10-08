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
        Schema::dropIfExists('maintenance_conditions');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('maintenance_conditions', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->boolean('mail_on_warning')->default(false);
            $table->boolean('mail_on_maintenance')->default(false);
            $table->integer('maintenance_recurrence_quantity')->default(0);
            $table->timestamp('maintenance_warning_date')->nullable();
            $table->timestamp('maintenance_date')->nullable();
            $table->decimal('quantity_for_warning', 10, 2)->default(0);
            $table->decimal('quantity_for_maintenance', 10, 2)->default(0);
            $table->string('recurrence_unit')->nullable();
            $table->decimal('price_per_unit', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignUuid('status_when_returned_id')->nullable()->constrained('statuses')->nullOnDelete();
            $table->foreignUuid('status_when_exceeded_id')->nullable()->constrained('statuses')->nullOnDelete();
            $table->foreignUuid('maintenance_category_id')->nullable()->constrained('maintenance_categories')->nullOnDelete();
            $table->foreignUuid('unit_of_measure_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->timestamps();

            $table->index('org_id');
            $table->index(['org_id', 'id']);
            $table->index(['org_id', 'item_id']);
            $table->index(['org_id', 'maintenance_category_id']);
            $table->index('status_when_returned_id');
            $table->index('status_when_exceeded_id');
            $table->index('unit_of_measure_id');
        });
    }
};
