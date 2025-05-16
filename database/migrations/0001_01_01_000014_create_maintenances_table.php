<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->text('remarks')->nullable();
            $table->string('invoice_nbr')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->timestamp('date_expected_back_from_maintenance')->nullable();
            $table->timestamp('date_back_from_maintenance')->nullable();
            $table->timestamp('date_in_maintenance')->nullable();
            $table->boolean('is_repair')->default(false);
            $table->string('import_id')->nullable();
            $table->string('import_source')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('status_out_id')->nullable();
            $table->unsignedBigInteger('status_in_id')->nullable();
            $table->index('employee_id');
            $table->index('supplier_id');
            $table->index('item_id');
            $table->index('status_out_id');
            $table->index('status_in_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
