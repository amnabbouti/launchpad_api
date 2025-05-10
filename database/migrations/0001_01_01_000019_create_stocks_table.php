<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            // Foreign keys
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();

            // Indexes
            $table->index('item_id');
            $table->index('location_id');
            $table->index('status_id');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
