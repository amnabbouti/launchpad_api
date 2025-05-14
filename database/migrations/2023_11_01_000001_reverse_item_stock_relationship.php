<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add stock_id to items table if it doesn't exist
        if (! Schema::hasColumn('items', 'stock_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->unsignedBigInteger('stock_id')->nullable()->after('user_id');
                $table->index('stock_id');
            });
        }

        // Remove item_id from stocks table
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->dropIndex(['item_id']);
            $table->dropColumn('item_id');
        });
    }

    public function down(): void
    {
        // Add item_id back to stocks table
        Schema::table('stocks', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id')->after('is_active');
            $table->index('item_id');
        });

        // Remove stock_id from items table
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['stock_id']);
            $table->dropColumn('stock_id');
        });
    }
};
