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
        Schema::create('api_key_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('token_id');
            $table->integer('requests_count')->default(0);
            $table->integer('requests_limit')->default(1000);
            $table->string('window_type', 10)->default('hour'); // hour, day, month
            $table->timestamp('window_start');
            $table->timestamp('window_end');
            $table->timestamps();

            $table->foreign('token_id')->references('id')->on('personal_access_tokens')->onDelete('cascade');
            $table->unique(['token_id', 'window_start', 'window_type']);
            $table->index(['token_id', 'window_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_key_rate_limits');
    }
};
