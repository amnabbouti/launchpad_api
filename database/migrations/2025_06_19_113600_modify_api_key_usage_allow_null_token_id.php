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
        Schema::table('api_key_usage', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['token_id']);
            
            // Make token_id nullable
            $table->unsignedBigInteger('token_id')->nullable()->change();
            
            // Re-add the foreign key constraint (nullable)
            $table->foreign('token_id')->references('id')->on('personal_access_tokens')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_key_usage', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['token_id']);
            
            // Make token_id not nullable again
            $table->unsignedBigInteger('token_id')->nullable(false)->change();
            
            // Re-add the original foreign key constraint
            $table->foreign('token_id')->references('id')->on('personal_access_tokens')->onDelete('cascade');
        });
    }
};
