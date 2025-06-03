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
        Schema::create('maintenance_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->decimal('value', 10, 2)->default(0);
            $table->foreignId('maintenance_condition_id')->constrained()->onDelete('cascade');
            $table->foreignId('maintenance_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index('org_id');
            $table->index(['org_id', 'id']);
            $table->index(['org_id', 'maintenance_condition_id']);
            $table->index(['org_id', 'maintenance_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_details');
    }
};
