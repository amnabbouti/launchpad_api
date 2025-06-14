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
        Schema::create('maintenance_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
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
            $table->foreignId('item_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('status_when_returned_id')->nullable()->constrained('statuses')->onDelete('set null');
            $table->foreignId('status_when_exceeded_id')->nullable()->constrained('statuses')->onDelete('set null');
            $table->foreignId('maintenance_category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('unit_of_measure_id')->nullable()->constrained()->onDelete('set null');
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_conditions');
    }
};
