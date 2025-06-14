<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->string('supplier_part_number')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('lead_time_days')->nullable()->comment('Lead time in days');
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['org_id', 'item_id', 'supplier_id']);
            $table->index('org_id');
            $table->index(['org_id', 'id']);
            $table->index(['org_id', 'item_id']);
            $table->index(['org_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_supplier');
    }
};
