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
        Schema::dropIfExists('maintenance_details');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('maintenance_details', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->decimal('value', 10, 2)->default(0);
            $table->foreignUuid('maintenance_condition_id')->constrained('maintenance_conditions')->cascadeOnDelete();
            $table->foreignUuid('maintenance_id')->constrained('maintenances')->cascadeOnDelete();
            $table->timestamps();

            $table->index('org_id');
            $table->index(['org_id', 'id']);
            $table->index(['org_id', 'maintenance_condition_id']);
            $table->index(['org_id', 'maintenance_id']);
        });
    }
};
