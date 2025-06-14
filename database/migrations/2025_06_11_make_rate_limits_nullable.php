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
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->integer('rate_limit_per_hour')->nullable()->default(null)->change();
            $table->integer('rate_limit_per_day')->nullable()->default(null)->change();
            $table->integer('rate_limit_per_month')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->integer('rate_limit_per_hour')->default(1000)->change();
            $table->integer('rate_limit_per_day')->default(24000)->change();
            $table->integer('rate_limit_per_month')->default(720000)->change();
        });
    }
};
