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
        Schema::create('item_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->string('supplier_part_number')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->integer('lead_time')->nullable()->comment('Lead time in days');
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            // Create a unique index on item_id and supplier_id
            $table->unique(['item_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_supplier');
    }
};
