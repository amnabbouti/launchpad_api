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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->text('remarks')->nullable();
            $table->string('invoice_nbr')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->timestamp('date_expected_back_from_maintenance')->nullable();
            $table->timestamp('date_back_from_maintenance')->nullable();
            $table->timestamp('date_in_maintenance')->nullable();
            $table->boolean('is_repair')->default(false);
            $table->string('import_id')->nullable();
            $table->string('import_source')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('maintainable_id')->nullable();
            $table->string('maintainable_type')->nullable();
            $table->foreignId('status_out_id')->nullable()->constrained('statuses')->onDelete('set null');
            $table->foreignId('status_in_id')->nullable()->constrained('statuses')->onDelete('set null');
            $table->timestamps();
            $table->unique(['org_id', 'invoice_nbr']);
            $table->index('org_id');
            $table->index(['org_id', 'id']);
            $table->index(['org_id', 'user_id']);
            $table->index(['org_id', 'supplier_id']);
            $table->index(['org_id', 'maintainable_id']);
            $table->index(['org_id', 'maintainable_type']);
            $table->index(['maintainable_id', 'maintainable_type']);
            $table->index('status_out_id');
            $table->index('status_in_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
