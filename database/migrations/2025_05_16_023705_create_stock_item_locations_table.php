<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockItemLocationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('stock_item_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('stock_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->date('moved_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['org_id', 'stock_item_id', 'location_id']);
            $table->index('org_id');
            $table->index(['org_id', 'id']);
            $table->index(['org_id', 'stock_item_id']);
            $table->index(['org_id', 'location_id']);
            $table->index('moved_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_item_locations');
    }
}
