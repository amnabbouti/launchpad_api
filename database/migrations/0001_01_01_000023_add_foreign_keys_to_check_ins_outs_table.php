<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_ins_outs', function (Blueprint $table) {
            // Add foreign key constraints
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->onDelete('restrict');

            $table->foreign('checkout_location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('restrict');

            $table->foreign('checkin_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->foreign('checkin_location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('restrict');

            $table->foreign('status_out_id')
                ->references('id')
                ->on('stock_statuses')
                ->onDelete('set null');

            $table->foreign('status_in_id')
                ->references('id')
                ->on('stock_statuses')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('check_ins_outs', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['user_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['checkout_location_id']);
            $table->dropForeign(['checkin_user_id']);
            $table->dropForeign(['checkin_location_id']);
            $table->dropForeign(['status_out_id']);
            $table->dropForeign(['status_in_id']);
        });
    }
};
