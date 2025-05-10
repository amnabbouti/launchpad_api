<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_details', function (Blueprint $table) {
            $table->id();
            $table->decimal('value', 10, 2)->default(0);

            // Foreign keys
            $table->unsignedBigInteger('maintenance_condition_id');
            $table->unsignedBigInteger('maintenance_id');

            // Indexes
            $table->index('maintenance_condition_id');
            $table->index('maintenance_id');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_details');
    }
};
