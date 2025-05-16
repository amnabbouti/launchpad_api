<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add foreign keys to descriptions table
        Schema::table('descriptions', function (Blueprint $table) {
            $table->foreign('maintenance_category_id')->references('id')->on('maintenance_categories')->onDelete('cascade');
        });

        // Add foreign keys to maintenance_conditions table
        Schema::table('maintenance_conditions', function (Blueprint $table) {
            $table->foreign('item_id')->references('id')->on('items')->onDelete('set null');
            $table->foreign('status_when_returned_id')->references('id')->on('stock_statuses')->onDelete('set null');
            $table->foreign('status_when_exceeded_id')->references('id')->on('stock_statuses')->onDelete('set null');
            $table->foreign('maintenance_category_id')->references('id')->on('maintenance_categories')->onDelete('set null');
            $table->foreign('unit_of_measure_id')->references('id')->on('unit_of_measures')->onDelete('set null');
        });

        // Add foreign keys to maintenances table
        Schema::table('maintenances', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            // $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('cascade'); // Removed: maintenance should not be linked to stock
            $table->foreign('item_id')->references('id')->on('items')->onDelete('set null'); // direct item relation
            $table->foreign('status_out_id')->references('id')->on('stock_statuses')->onDelete('set null');
            $table->foreign('status_in_id')->references('id')->on('stock_statuses')->onDelete('set null');
        });

        // Add foreign keys to maintenance_details table
        Schema::table('maintenance_details', function (Blueprint $table) {
            $table->foreign('maintenance_condition_id')->references('id')->on('maintenance_conditions')->onDelete('cascade');
            $table->foreign('maintenance_id')->references('id')->on('maintenances')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Remove foreign keys from descriptions table
        Schema::table('descriptions', function (Blueprint $table) {
            $table->dropForeign(['maintenance_category_id']);
        });

        // Remove foreign keys from maintenance_conditions table
        Schema::table('maintenance_conditions', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->dropForeign(['status_when_returned_id']);
            $table->dropForeign(['status_when_exceeded_id']);
            $table->dropForeign(['maintenance_category_id']);
            $table->dropForeign(['unit_of_measure_id']);
        });

        // Remove foreign keys from maintenances table
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['stock_id']);
            $table->dropForeign(['status_out_id']);
            $table->dropForeign(['status_in_id']);
        });

        // Remove foreign keys from maintenance_details table
        Schema::table('maintenance_details', function (Blueprint $table) {
            $table->dropForeign(['maintenance_condition_id']);
            $table->dropForeign(['maintenance_id']);
        });
    }
};
