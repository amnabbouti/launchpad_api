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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->string('batch_number')->nullable();
            $table->date('received_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
